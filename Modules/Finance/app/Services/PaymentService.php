<?php

namespace Modules\Finance\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Models\PaymentAllocation;
use Modules\Finance\Models\PaymentRecord;

class PaymentService
{
    public function __construct(
        private FinNumberService $numbers,
        private JournalService $journals,
        private AccountService $accounts,
        private FinancePeriodService $periods,
        private PayableService $payables,
        private ReceivableService $receivables,
        private AuditService $audit,
    ) {}

    /**
     * Record a payment (recording != execution). Dr/Cr cash-bank vs control
     * account, then allocate to the target document.
     * $data: company_id, direction(in|out), method, amount, payment_date,
     *        bank_account_id?, document_type(supplier_invoice|customer_invoice), document_id
     */
    public function record(array $data, int $userId): PaymentRecord
    {
        return DB::transaction(function () use ($data, $userId) {
            $payment = PaymentRecord::create([
                'reference'   => $this->numbers->for('payment'),
                'company_id'  => $data['company_id'],
                'direction'   => $data['direction'],
                'method'      => $data['method'],
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'payment_date' => $data['payment_date'],
                'amount'      => $data['amount'],
                'payer_payee' => $data['payer_payee'] ?? null,
                'external_ref' => $data['external_ref'] ?? null,
                'status'      => 'recorded',
                'created_by'  => $userId,
            ]);

            $period = $this->periods->openPeriodFor($payment->company_id, $payment->payment_date->toDateString())
                ?? throw ValidationException::withMessages(['period' => 'No open financial period for payment date.']);
            $cash = $payment->bank_account_id
                ? \Modules\Finance\Models\BankAccount::findOrFail($payment->bank_account_id)->glAccount
                : $this->accounts->mapped($payment->company_id, 'cash');
            $doc = $this->resolveDocument($data['document_type'], $data['document_id']);

            $isIn = $payment->direction === 'in';
            $controlKey = $isIn ? 'accounts_receivable' : 'accounts_payable';
            $control = $this->accounts->mapped($payment->company_id, $controlKey);

            $journal = $this->journals->create([
                'company_id' => $payment->company_id, 'period_id' => $period->id,
                'type' => $isIn ? 'cash_receipt' : 'cash_payment',
                'journal_date' => $payment->payment_date->toDateString(),
                'memo' => "Payment {$payment->reference}",
                'source_type' => 'payment', 'source_id' => $payment->id,
                'lines' => $isIn
                    ? [['account_id'=>$cash->id,'debit'=>(float)$payment->amount,'credit'=>0],
                       ['account_id'=>$control->id,'debit'=>0,'credit'=>(float)$payment->amount]]
                    : [['account_id'=>$control->id,'debit'=>(float)$payment->amount,'credit'=>0],
                       ['account_id'=>$cash->id,'debit'=>0,'credit'=>(float)$payment->amount]],
            ], $userId);
            $this->journals->post($journal, $userId);

            $payment->update(['journal_id' => $journal->id]);
            if ($doc instanceof \Modules\Finance\Models\SupplierInvoice) {
                $this->payables->applyPayment($doc, $payment);
            } elseif ($doc instanceof \Modules\Finance\Models\CustomerInvoice) {
                $this->receivables->applyReceipt($doc, $payment);
            }
            $payment->update(['status' => 'allocated']);
            $this->audit->log('finance.payment.recorded', $payment, new: ['amount' => (float) $payment->amount]);
            return $payment->fresh();
        });
    }

    public function unallocated(PaymentRecord $payment): float
    {
        $allocated = PaymentAllocation::where('payment_id', $payment->id)->sum('amount');
        return (float) $payment->amount - (float) $allocated;
    }

    private function resolveDocument(string $type, int $id)
    {
        return match ($type) {
            'supplier_invoice' => \Modules\Finance\Models\SupplierInvoice::findOrFail($id),
            'customer_invoice' => \Modules\Finance\Models\CustomerInvoice::findOrFail($id),
            'expense_claim'    => \Modules\Finance\Models\ExpenseClaim::findOrFail($id),
            default => throw ValidationException::withMessages(['document' => "Unknown document type [{$type}]."]),
        };
    }
}
