<?php

namespace Modules\Finance\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Jobs\TransmitEfdReceipt;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\EfdReceipt;
use App\Services\SettingsService;

/**
 * EFD / fiscal receipt groundwork (spec §15.4).
 *
 * Builds the TRA-oriented payload, persists receipt lifecycle and
 * queues transmission. Actual gateway transmission stays DEFERRED —
 * TransmitEfdReceipt only runs when finance.efd.enabled + gateway
 * settings are configured; until then receipts remain 'queued' with
 * their payload + evidence stored locally.
 */
class EfdReceiptService
{
    public function __construct(
        private AuditService $audit,
        private SettingsService $settings,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) $this->settings->get('finance.efd.enabled', false);
    }

    /** Create a draft receipt for an issued invoice (idempotent per invoice). */
    public function issue(CustomerInvoice $invoice, ?int $userId = null): EfdReceipt
    {
        abort_unless(in_array($invoice->status, ['posted', 'partially_paid', 'paid'], true),
            422, 'Receipts can only be issued for issued/paid invoices.');

        return EfdReceipt::firstOrCreate(
            ['customer_invoice_id' => $invoice->id],
            [
                'reference' => $this->nextReference(),
                'branch_id' => $invoice->customer?->branch_id,
                'payload' => $this->buildPayload($invoice),
                'created_by' => $userId ?? $invoice->created_by,
            ]);
    }

    /** Queue the draft for transmission (or hold when gateway not configured). */
    public function queue(EfdReceipt $receipt): EfdReceipt
    {
        abort_unless($receipt->status === 'draft', 422, 'Only draft receipts can be queued.');
        $receipt->update(['status' => 'queued', 'queued_at' => now()]);

        if ($this->isEnabled()) {
            TransmitEfdReceipt::dispatch($receipt);
        }
        return $receipt;
    }

    /** Cancel a draft/queued receipt — never touch transmitted ones. */
    public function void(EfdReceipt $receipt, string $reason): EfdReceipt
    {
        abort_unless(in_array($receipt->status, ['draft', 'queued', 'failed'], true),
            422, 'Only draft, queued or failed receipts can be voided.');
        $receipt->update(['status' => 'void']);
        $this->audit->log('finance.efd.voided', $receipt, new: ['reason' => $reason]);
        return $receipt;
    }

    /**
     * TRA-oriented payload — structured so a gateway adapter can map it
     * to the actual EFD API once requirements are confirmed.
     */
    public function buildPayload(CustomerInvoice $invoice): array
    {
        $invoice->loadMissing('lines.taxRate', 'customer');
        return [
            'invoice_reference' => $invoice->reference,
            'invoice_date' => $invoice->invoice_date->toDateString(),
            'currency' => $invoice->currency,
            'customer' => [
                'name' => $invoice->customer->name ?? null,
                'tin' => $invoice->customer->tin ?? null,
            ],
            'items' => $invoice->lines->map(fn ($l) => [
                'description' => $l->description,
                'quantity' => (float) $l->quantity,
                'unit_price' => (float) $l->unit_price,
                'discount' => (float) $l->discount,
                'amount' => (float) $l->amount,
                'tax_rate' => $l->taxRate ? [
                    'code' => $l->taxRate->code ?? null,
                    'rate' => (float) $l->taxRate->rate,
                ] : null,
            ])->all(),
            'totals' => [
                'subtotal' => (float) $invoice->subtotal,
                'tax' => (float) $invoice->tax_amount,
                'total' => (float) $invoice->total,
            ],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function nextReference(): string
    {
        $prefix = 'EFD-' . now()->format('Y') . '-';
        $max = EfdReceipt::where('reference', 'like', "{$prefix}%")
            ->lockForUpdate()->max('reference');
        return $prefix . str_pad((string) ($max ? ((int) substr($max, -6)) + 1 : 1), 6, '0', STR_PAD_LEFT);
    }
}
