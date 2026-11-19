<?php

namespace Modules\Finance\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Models\PaymentAllocation;
use Modules\Finance\Models\PaymentRecord;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\SupplierInvoice;

class PayableService
{
    public function __construct(
        private FinNumberService $numbers,
        private JournalService $journals,
        private AccountService $accounts,
        private FinancePeriodService $periods,
        private AuditService $audit,
    ) {}

    public function createInvoice(array $data, int $userId): SupplierInvoice
    {
        return DB::transaction(function () use ($data, $userId) {
            $invoice = SupplierInvoice::create([
                'reference'           => $this->numbers->for('supplier_invoice'),
                'company_id'          => $data['company_id'],
                'supplier_id'         => $data['supplier_id'],
                'supplier_invoice_no' => $data['supplier_invoice_no'] ?? null,
                'invoice_date'        => $data['invoice_date'],
                'due_date'            => $data['due_date'] ?? null,
                'currency'            => $data['currency'] ?? 'TZS',
                'status'              => 'draft',
                'created_by'          => $userId,
            ]);
            $subtotal = 0;
            foreach ($data['lines'] as $line) {
                $amount = round((float) $line['quantity'] * (float) $line['unit_price'], 2);
                $invoice->lines()->create([...$line, 'amount' => $amount]);
                $subtotal += $amount;
            }
            $tax = (float) ($data['tax_amount'] ?? 0);
            $invoice->update(['subtotal' => $subtotal, 'tax_amount' => $tax, 'total' => $subtotal + $tax]);
            $this->audit->log('finance.payable.created', $invoice, new: ['total' => $invoice->total]);
            return $invoice->fresh('lines');
        });
    }

    /** Post: Dr expense/asset lines, Cr accounts payable. Creates GL via journal engine. */
    public function post(SupplierInvoice $invoice, int $userId): SupplierInvoice
    {
        if (! in_array($invoice->status, ['draft', 'submitted', 'approved'], true)) {
            throw ValidationException::withMessages(['invoice' => "Cannot post invoice from status [{$invoice->status}]."]);
        }
        return DB::transaction(function () use ($invoice, $userId) {
            $invoice->refresh();
            $period = $this->periods->openPeriodFor($invoice->company_id, $invoice->invoice_date->toDateString())
                ?? throw ValidationException::withMessages(['period' => 'No open financial period for invoice date.']);
            $ap = $this->accounts->mapped($invoice->company_id, 'accounts_payable');

            $lines = $invoice->lines->map(fn ($l) => [
                'account_id'    => $l->account_id,
                'debit'         => $l->amount,
                'credit'        => 0,
                'description'   => $l->description,
                'branch_id'     => $l->branch_id,
                'department_id' => $l->department_id,
            ])->all();
            if ($invoice->tax_amount > 0) {
                $lines[] = ['account_id' => $this->accounts->mapped($invoice->company_id, 'tax_receivable')->id,
                            'debit' => (float) $invoice->tax_amount, 'credit' => 0, 'description' => 'Input tax'];
            }
            $lines[] = ['account_id' => $ap->id, 'debit' => 0, 'credit' => (float) $invoice->total,
                        'description' => "AP {$invoice->reference}"];

            $journal = $this->journals->create([
                'company_id' => $invoice->company_id, 'period_id' => $period->id,
                'type' => 'purchase', 'journal_date' => $invoice->invoice_date->toDateString(),
                'memo' => "Supplier invoice {$invoice->reference}",
                'source_type' => 'supplier_invoice', 'source_id' => $invoice->id,
                'lines' => $lines,
            ], $userId);
            $this->journals->post($journal, $userId);

            $invoice->update(['status' => 'posted', 'journal_id' => $journal->id]);
            $this->audit->log('finance.payable.posted', $invoice, new: ['journal' => $journal->reference]);
            return $invoice->fresh();
        });
    }

    /** Apply a payment to an invoice; flips status on partial/full settlement. */
    public function applyPayment(SupplierInvoice $invoice, PaymentRecord $payment): void
    {
        DB::transaction(function () use ($invoice, $payment) {
            $already = PaymentAllocation::where('document_type', 'supplier_invoice')
                ->where('document_id', $invoice->id)->sum('amount');
            $outstanding = (float) $invoice->total - (float) $already;
            if ($payment->amount > $outstanding + 0.001) {
                throw ValidationException::withMessages(['payment' => "Payment exceeds outstanding balance ({$outstanding})."]);
            }
            PaymentAllocation::create([
                'payment_id' => $payment->id, 'document_type' => 'supplier_invoice',
                'document_id' => $invoice->id, 'amount' => $payment->amount,
            ]);
            $paid = $already + $payment->amount;
            $invoice->update(['paid_amount' => $paid,
                'status' => $paid >= (float) $invoice->total - 0.001 ? 'paid' : 'partially_paid']);
        });
    }

    public function aging(int $companyId): array
    {
        $invoices = SupplierInvoice::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'partially_paid'])->get();
        $buckets = ['current' => 0.0, '1_30' => 0.0, '31_60' => 0.0, '61_90' => 0.0, '90_plus' => 0.0];
        foreach ($invoices as $inv) {
            $outstanding = $inv->total - $inv->paid_amount;
            if ($outstanding <= 0) continue;
            $days = $inv->due_date ? now()->diffInDays($inv->due_date, false) : 0;
            $days = abs($days);
            if (! $inv->due_date || $inv->due_date->isFuture()) $buckets['current'] += $outstanding;
            elseif ($days <= 30) $buckets['1_30'] += $outstanding;
            elseif ($days <= 60) $buckets['31_60'] += $outstanding;
            elseif ($days <= 90) $buckets['61_90'] += $outstanding;
            else $buckets['90_plus'] += $outstanding;
        }
        return $buckets;
    }
}
