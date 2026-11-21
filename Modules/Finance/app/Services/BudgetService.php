<?php

namespace Modules\Finance\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Models\Budget;
use Modules\Finance\Models\BudgetLine;
use Modules\Finance\Models\GeneralLedgerEntry;

class BudgetService
{
    public function __construct(
        private FinNumberService $numbers,
        private FinApprovalService $approvals,
        private AuditService $audit,
    ) {}

    public function create(array $data, int $userId): Budget
    {
        return DB::transaction(function () use ($data, $userId) {
            $budget = Budget::create([
                'reference'  => $this->numbers->for('budget'),
                'company_id' => $data['company_id'],
                'year_id'    => $data['year_id'],
                'branch_id'  => $data['branch_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'name'       => $data['name'],
                'control_policy' => $data['control_policy'] ?? 'warn',
                'status'     => 'draft',
                'created_by' => $userId,
            ]);
            foreach ($data['lines'] as $line) {
                $budget->lines()->create(['account_id' => $line['account_id'], 'amount' => $line['amount']]);
            }
            return $budget;
        });
    }

    public function submit(Budget $budget, int $userId): Budget
    {
        if ($budget->status !== 'draft') {
            throw ValidationException::withMessages(['budget' => 'Only draft budgets can be submitted.']);
        }
        $budget->update(['status' => 'submitted']);
        $this->approvals->createChain($budget, ['Finance and Accounting']);
        $this->audit->log('finance.budget.submitted', $budget);
        return $budget;
    }

    public function activate(Budget $budget): Budget
    {
        if (! $this->approvals->isFullyApproved($budget)) {
            throw ValidationException::withMessages(['budget' => 'Budget requires approval before activation.']);
        }
        $budget->update(['status' => 'active']);
        $this->audit->log('finance.budget.activated', $budget);
        return $budget;
    }

    /**
     * Check + consume budget for a spend. Policy: warn | approval | block.
     * Returns ['ok'=>bool, 'action'=>..., 'utilization'=>%]
     */
    public function consume(Budget $budget, int $accountId, float $amount): array
    {
        return DB::transaction(function () use ($budget, $accountId, $amount) {
            /** @var BudgetLine|null $line */
            $line = BudgetLine::where('budget_id', $budget->id)->where('account_id', $accountId)
                ->lockForUpdate()->first();
            if (! $line) return ['ok' => true, 'action' => 'no_budget_line', 'utilization' => null];
            if ($budget->status !== 'active') {
                return ['ok' => $budget->control_policy !== 'block', 'action' => 'budget_not_active', 'utilization' => null];
            }
            $projected = (float) $line->consumed + $amount;
            $utilization = $line->amount > 0 ? round($projected / $line->amount * 100, 2) : 0;
            if ($projected > (float) $line->amount) {
                if ($budget->control_policy === 'block') {
                    return ['ok' => false, 'action' => 'blocked_over_budget', 'utilization' => $utilization];
                }
                $line->increment('consumed', $amount);
                return ['ok' => true, 'action' => $budget->control_policy === 'approval' ? 'requires_approval' : 'warned', 'utilization' => $utilization];
            }
            $line->increment('consumed', $amount);
            return ['ok' => true, 'action' => 'consumed', 'utilization' => $utilization];
        });
    }

    /** Actual spend from posted GL for variance reporting. */
    public function actual(Budget $budget, int $accountId): float
    {
        return (float) GeneralLedgerEntry::where('company_id', $budget->company_id)
            ->where('account_id', $accountId)
            ->when($budget->department_id, fn ($q) => $q->where('department_id', $budget->department_id))
            ->when($budget->branch_id, fn ($q) => $q->where('branch_id', $budget->branch_id))
            ->sum('debit')
            - (float) GeneralLedgerEntry::where('company_id', $budget->company_id)
            ->where('account_id', $accountId)
            ->when($budget->department_id, fn ($q) => $q->where('department_id', $budget->department_id))
            ->when($budget->branch_id, fn ($q) => $q->where('branch_id', $budget->branch_id))
            ->sum('credit');
    }
}
