<?php

namespace Modules\Finance\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Models\ExpenseClaim;

class ExpenseService
{
    private const STATES = ['draft','submitted','manager_review','finance_review','approved','posted','paid','rejected'];

    public function __construct(
        private FinNumberService $numbers,
        private JournalService $journals,
        private AccountService $accounts,
        private FinancePeriodService $periods,
        private FinApprovalService $approvals,
        private AuditService $audit,
    ) {}

    public function submit(array $data, int $userId): ExpenseClaim
    {
        return DB::transaction(function () use ($data, $userId) {
            $claim = ExpenseClaim::create([
                'reference'    => $this->numbers->for('expense'),
                'company_id'   => $data['company_id'],
                'employee_id'  => $data['employee_id'] ?? null,
                'category_id'  => $data['category_id'],
                'branch_id'    => $data['branch_id'] ?? null,
                'department_id'=> $data['department_id'] ?? null,
                'expense_date' => $data['expense_date'],
                'description'  => $data['description'],
                'amount'       => $data['amount'],
                'tax_amount'   => $data['tax_amount'] ?? 0,
                'receipt_file_id' => $data['receipt_file_id'] ?? null,
                'status'       => 'submitted',
                'created_by'   => $userId,
            ]);
            $chain = $claim->category?->approval_threshold && $claim->amount > $claim->category->approval_threshold
                ? ['finance_manager', 'finance_director'] : ['finance_manager'];
            $this->approvals->createChain($claim, $chain);
            $this->audit->log('finance.expense.submitted', $claim, new: ['amount' => (float) $claim->amount]);
            return $claim;
        });
    }

    /** Post to GL only after full approval. Dr expense account, Cr cash/AP-employee. */
    public function post(ExpenseClaim $claim, int $userId): ExpenseClaim
    {
        if ($claim->status === 'posted' || $claim->status === 'paid') {
            throw ValidationException::withMessages(['claim' => 'Expense already posted.']);
        }
        if (! $this->approvals->isFullyApproved($claim)) {
            throw ValidationException::withMessages(['claim' => 'Expense requires full approval before posting.']);
        }
        return DB::transaction(function () use ($claim, $userId) {
            $period = $this->periods->openPeriodFor($claim->company_id, $claim->expense_date->toDateString())
                ?? throw ValidationException::withMessages(['period' => 'No open financial period for expense date.']);
            $debitAccount = $claim->category->account_id
                ?? $this->accounts->mapped($claim->company_id, 'expense_default')->id;
            $creditAccount = $this->accounts->mapped($claim->company_id, 'cash')->id;

            $lines = [
                ['account_id' => $debitAccount, 'debit' => (float) $claim->amount, 'credit' => 0,
                 'description' => $claim->description, 'branch_id' => $claim->branch_id, 'department_id' => $claim->department_id],
                ['account_id' => $creditAccount, 'debit' => 0, 'credit' => (float) $claim->amount,
                 'description' => "Expense {$claim->reference}"],
            ];
            $journal = $this->journals->create([
                'company_id' => $claim->company_id, 'period_id' => $period->id,
                'type' => 'cash_payment', 'journal_date' => $claim->expense_date->toDateString(),
                'memo' => "Expense {$claim->reference}", 'source_type' => 'expense_claim',
                'source_id' => $claim->id, 'lines' => $lines,
            ], $userId);
            $this->journals->post($journal, $userId);

            $claim->update(['status' => 'posted', 'journal_id' => $journal->id]);
            $this->audit->log('finance.expense.posted', $claim, new: ['journal' => $journal->reference]);
            return $claim->fresh();
        });
    }
}
