<?php

namespace Modules\Finance\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\PaymentAllocation;
use Modules\Finance\Models\PaymentRecord;

class ReceivableService
{
    public function __construct(
        private FinNumberService $numbers,
        private JournalService $journals,
        private AccountService $accounts,
        private FinancePeriodService $periods,
        private AuditService $audit,
    ) {}

    public function createInvoice(array $data, int $userId): CustomerInvoice
    {
        return DB::transaction(function () use ($data, $userId) {
            $invoice = CustomerInvoice::create([
                'reference'    => $this->numbers->for('customer_invoice'),
                'company_id'   => $data['company_id'],
                'customer_id'  => $data['customer_id'],
                'package_id'   => $data['package_id'] ?? null,
                'type'         => $data['type'] ?? 'invoice',
                'invoice_date' => $data['invoice_date'],
                'due_date'     => $data['due_date'] ?? null,
                'currency'     => $data['currency'] ?? 'TZS',
                'status'       => 'draft',
                'created_by'   => $userId,
            ]);
            $subtotal = 0;
            foreach ($data['lines'] as $line) {
                $amount = round((float) $line['quantity'] * (float) $line['unit_price'] - (float) ($line['discount'] ?? 0), 2);
                $invoice->lines()->create([...$line, 'amount' => $amount]);
                $subtotal += $amount;
            }
            $tax = (float) ($data['tax_amount'] ?? 0);
            $invoice->update(['subtotal' => $subtotal, 'tax_amount' => $tax, 'total' => $subtotal + $tax]);
            $this->audit->log('finance.receivable.created', $invoice, new: ['total' => $invoice->total]);
            return $invoice->fresh('lines');
        });
    }

    /** Post: Dr accounts receivable, Cr revenue lines + tax payable. */
    public function post(CustomerInvoice $invoice, int $userId): CustomerInvoice
    {
        if (! in_array($invoice->status, ['draft', 'submitted', 'approved'], true)) {
            throw ValidationException::withMessages(['invoice' => "Cannot post invoice from status [{$invoice->status}]."]);
        }
        return DB::transaction(function () use ($invoice, $userId) {
            $invoice->refresh();
            $period = $this->periods->openPeriodFor($invoice->company_id, $invoice->invoice_date->toDateString())
                ?? throw ValidationException::withMessages(['period' => 'No open financial period for invoice date.']);
            $ar = $this->accounts->mapped($invoice->company_id, 'accounts_receivable');

            $lines = [['account_id' => $ar->id, 'debit' => (float) $invoice->total, 'credit' => 0,
                       'description' => "AR {$invoice->reference}"]];
            foreach ($invoice->lines as $l) {
                $lines[] = ['account_id' => $l->account_id, 'debit' => 0, 'credit' => $l->amount,
                            'description' => $l->description];
            }
            if ($invoice->tax_amount > 0) {
                $lines[] = ['account_id' => $this->accounts->mapped($invoice->company_id, 'tax_payable')->id,
                            'debit' => 0, 'credit' => (float) $invoice->tax_amount, 'description' => 'Output tax'];
            }

            $journal = $this->journals->create([
                'company_id' => $invoice->company_id, 'period_id' => $period->id,
                'type' => 'sales', 'journal_date' => $invoice->invoice_date->toDateString(),
                'memo' => "Customer invoice {$invoice->reference}",
                'source_type' => 'customer_invoice', 'source_id' => $invoice->id,
                'lines' => $lines,
            ], $userId);
            $this->journals->post($journal, $userId);

            $invoice->update(['status' => 'posted', 'journal_id' => $journal->id]);
            $this->audit->log('finance.receivable.posted', $invoice, new: ['journal' => $journal->reference]);

            // EFD groundwork: draft a fiscal receipt for every posted invoice
            // (stays draft until queued; transmission is a separate step).
            app(EfdReceiptService::class)->issue($invoice, $userId);

            return $invoice->fresh();
        });
    }

    public function applyReceipt(CustomerInvoice $invoice, PaymentRecord $receipt): void
    {
        DB::transaction(function () use ($invoice, $receipt) {
            $already = PaymentAllocation::where('document_type', 'customer_invoice')
                ->where('document_id', $invoice->id)->sum('amount');
            $outstanding = (float) $invoice->total - (float) $already;
            if ($receipt->amount > $outstanding + 0.001) {
                throw ValidationException::withMessages(['receipt' => "Receipt exceeds outstanding balance ({$outstanding})."]);
            }
            PaymentAllocation::create([
                'payment_id' => $receipt->id, 'document_type' => 'customer_invoice',
                'document_id' => $invoice->id, 'amount' => $receipt->amount,
            ]);
            $received = $already + $receipt->amount;
            $invoice->update(['received_amount' => $received,
                'status' => $received >= (float) $invoice->total - 0.001 ? 'paid' : 'partially_paid']);
        });
    }

    public function aging(int $companyId): array
    {
        $invoices = CustomerInvoice::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'partially_paid'])->get();
        $buckets = ['current' => 0.0, '1_30' => 0.0, '31_60' => 0.0, '61_90' => 0.0, '90_plus' => 0.0];
        foreach ($invoices as $inv) {
            $outstanding = $inv->total - $inv->received_amount;
            if ($outstanding <= 0) continue;
            if (! $inv->due_date || $inv->due_date->isFuture()) { $buckets['current'] += $outstanding; continue; }
            $days = abs(now()->diffInDays($inv->due_date, false));
            if ($days <= 30) $buckets['1_30'] += $outstanding;
            elseif ($days <= 60) $buckets['31_60'] += $outstanding;
            elseif ($days <= 90) $buckets['61_90'] += $outstanding;
            else $buckets['90_plus'] += $outstanding;
        }
        return $buckets;
    }
}
