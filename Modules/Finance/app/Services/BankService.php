<?php

namespace Modules\Finance\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\BankReconciliation;
use Modules\Finance\Models\BankTransaction;
use Modules\Finance\Models\GeneralLedgerEntry;
use Modules\Finance\Models\PaymentRecord;

class BankService
{
    public function __construct(
        private FinNumberService $numbers,
        private AccountService $accounts,
        private AuditService $audit,
    ) {}

    /** Import bank statement rows (CSV adapter feeds this; no direct bank API claimed). */
    public function importTransactions(BankAccount $account, array $rows): int
    {
        return DB::transaction(function () use ($account, $rows) {
            $count = 0;
            foreach ($rows as $row) {
                $exists = BankTransaction::where('bank_account_id', $account->id)
                    ->whereDate('txn_date', $row['txn_date'])
                    ->where('description', $row['description'])
                    ->where('debit', $row['debit'] ?? 0)
                    ->where('credit', $row['credit'] ?? 0)
                    ->exists();
                if ($exists) continue;
                BankTransaction::create([
                    'bank_account_id' => $account->id,
                    'txn_date'   => $row['txn_date'],
                    'description'=> $row['description'],
                    'debit'      => $row['debit'] ?? 0,
                    'credit'     => $row['credit'] ?? 0,
                    'reference'  => $row['reference'] ?? null,
                    'source'     => 'import',
                ]);
                $count++;
            }
            return $count;
        });
    }

    /** Book balance = opening + posted GL movement on the mapped bank GL account. */
    public function bookBalance(BankAccount $account, ?string $asOf = null): float
    {
        $q = GeneralLedgerEntry::where('account_id', $account->account_id);
        if ($asOf) $q->whereDate('entry_date', '<=', $asOf);
        return (float) $account->opening_balance + (float) $q->sum('debit') - (float) $q->sum('credit');
    }

    public function statementBalance(BankAccount $account, ?string $asOf = null): float
    {
        $q = BankTransaction::where('bank_account_id', $account->id);
        if ($asOf) $q->whereDate('txn_date', '<=', $asOf);
        return (float) $account->opening_balance + (float) $q->sum('credit') - (float) $q->sum('debit');
    }

    /** Reconcile: difference between book and statement; auto-suggest payment matches. */
    public function reconcile(BankAccount $account, string $statementDate, float $statementBalance, int $userId): BankReconciliation
    {
        $book = $this->bookBalance($account, $statementDate);
        $recon = BankReconciliation::create([
            'reference' => $this->numbers->for('reconciliation'),
            'bank_account_id' => $account->id,
            'statement_date' => $statementDate,
            'statement_balance' => $statementBalance,
            'book_balance' => $book,
            'difference' => round($statementBalance - $book, 2),
            'status' => 'open',
            'prepared_by' => $userId,
        ]);
        $this->audit->log('finance.recon.prepared', $recon, new: ['difference' => (float) $recon->difference]);
        return $recon;
    }

    /** Match unmatched statement transactions to recorded payments by amount+date. */
    public function suggestMatches(BankAccount $account): array
    {
        $unmatched = BankTransaction::where('bank_account_id', $account->id)
            ->whereNull('matched_payment_id')->get();
        $suggestions = [];
        foreach ($unmatched as $txn) {
            $amount = $txn->credit > 0 ? $txn->credit : $txn->debit;
            $direction = $txn->credit > 0 ? 'in' : 'out';
            $payment = PaymentRecord::where('company_id', $account->company_id)
                ->where('direction', $direction)->where('amount', $amount)
                ->whereDate('payment_date', '<=', $txn->txn_date)
                ->whereDoesntHave('allocations', fn ($q) => $q->whereRaw('1=0'))
                ->first();
            if ($payment) $suggestions[] = ['transaction' => $txn, 'payment' => $payment];
        }
        return $suggestions;
    }

    public function approve(BankReconciliation $recon, int $userId): BankReconciliation
    {
        if ($recon->prepared_by === $userId) {
            throw ValidationException::withMessages(['recon' => 'Preparer cannot approve their own reconciliation.']);
        }
        $recon->update(['status' => 'approved', 'approved_by' => $userId]);
        $this->audit->log('finance.recon.approved', $recon);
        return $recon;
    }
}
