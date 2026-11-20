<?php

namespace Modules\Finance\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Models\ChartOfAccount;
use Modules\Finance\Models\InterBranchEntry;
use Modules\Finance\Models\Journal;

/**
 * Spec §8.3 — inter-branch ledger.
 *
 * Costs incurred by one branch on behalf of another (Dubai spends;
 * Tanzania collects revenue) are recorded as ledger entries and
 * reconciled monthly. Settlement posts one balanced journal per
 * branch pair:
 *
 *   Dr Inter-branch receivable (1400)      @ spent branch
 *   Cr Dubai handling recovery (4300)      @ spent branch
 *   Dr Inter-branch handling charge (5300) @ owed branch
 *   Cr Inter-branch payable (2300)         @ owed branch
 */
class InterBranchService
{
    public function __construct(
        private JournalService $journals,
        private FinancePeriodService $periods,
        private AuditService $audit,
    ) {}

    public function record(int $companyId, int $spentBranchId, int $owedBranchId,
        float $amount, string $currency, string $description,
        ?string $sourceType = null, ?int $sourceId = null,
        ?int $userId = null, ?string $date = null): InterBranchEntry
    {
        abort_if($spentBranchId === $owedBranchId, 422, 'Spent and owed branches must differ.');
        abort_if($amount <= 0, 422, 'Amount must be positive.');

        return InterBranchEntry::forceCreate([
            'reference' => $this->nextRef(), 'company_id' => $companyId,
            'spent_branch_id' => $spentBranchId, 'owed_branch_id' => $owedBranchId,
            'description' => $description, 'amount' => $amount, 'currency' => strtoupper($currency),
            'source_type' => $sourceType, 'source_id' => $sourceId,
            'entry_date' => $date ?? now()->toDateString(),
            'status' => 'open', 'created_by' => $userId,
        ]);
    }

    /** Monthly reconciliation view: entries + totals per branch pair. */
    public function reconciliation(int $companyId, int $year, int $month): array
    {
        $entries = InterBranchEntry::with('spentBranch', 'owedBranch')
            ->where('company_id', $companyId)
            ->whereYear('entry_date', $year)->whereMonth('entry_date', $month)
            ->orderBy('entry_date')->get();

        $pairs = $entries->where('status', '!=', 'void')
            ->groupBy(fn ($e) => "{$e->spent_branch_id}:{$e->owed_branch_id}:{$e->currency}")
            ->map(fn ($g) => [
                'spent_branch' => $g->first()->spentBranch?->name,
                'owed_branch'  => $g->first()->owedBranch?->name,
                'currency'     => $g->first()->currency,
                'open_total'   => (float) $g->where('status', 'open')->sum('amount'),
                'settled_total'=> (float) $g->where('status', 'settled')->sum('amount'),
                'entries'      => $g->count(),
            ])->values();

        return ['entries' => $entries, 'pairs' => $pairs, 'year' => $year, 'month' => $month];
    }

    /** Settle open entries → balanced intercompany journal per branch pair. */
    public function settle(int $companyId, array $entryIds, int $userId): array
    {
        $entries = InterBranchEntry::where('company_id', $companyId)
            ->whereIn('id', $entryIds)->where('status', 'open')->get();
        abort_if($entries->isEmpty(), 422, 'No open entries selected.');

        $journals = [];
        foreach ($entries->groupBy(fn ($e) => "{$e->spent_branch_id}:{$e->owed_branch_id}:{$e->currency}") as $group) {
            $journals[] = DB::transaction(function () use ($companyId, $group, $userId) {
                $first = $group->first();
                $total = (float) $group->sum('amount');
                $period = $this->periods->openPeriodFor($companyId, now()->toDateString())
                    ?? throw ValidationException::withMessages(['period' => 'No open financial period.']);

                [$recv, $pay, $recov, $charge] = $this->accounts($companyId);

                $journal = $this->journals->create([
                    'company_id' => $companyId, 'period_id' => $period->id,
                    'type' => 'general', 'journal_date' => now()->toDateString(),
                    'currency' => $first->currency,
                    'memo' => "Inter-branch settlement {$first->spentBranch?->name}→{$first->owedBranch?->name}",
                    'source_type' => 'inter_branch', 'source_id' => $first->id,
                    'lines' => [
                        ['account_id' => $recv->id, 'branch_id' => $first->spent_branch_id,
                         'debit' => $total, 'credit' => 0, 'description' => 'Inter-branch receivable'],
                        ['account_id' => $recov->id, 'branch_id' => $first->spent_branch_id,
                         'debit' => 0, 'credit' => $total, 'description' => 'Handling recovery'],
                        ['account_id' => $charge->id, 'branch_id' => $first->owed_branch_id,
                         'debit' => $total, 'credit' => 0, 'description' => 'Inter-branch handling charge'],
                        ['account_id' => $pay->id, 'branch_id' => $first->owed_branch_id,
                         'debit' => 0, 'credit' => $total, 'description' => 'Inter-branch payable'],
                    ],
                ], $userId);
                $journal = $this->journals->post($journal, $userId);

                InterBranchEntry::whereIn('id', $group->pluck('id'))
                    ->update(['status' => 'settled', 'journal_id' => $journal->id, 'settled_at' => now()]);
                $this->audit->log('finance.inter_branch.settled', $journal,
                    new: ['entries' => $group->count(), 'total' => $total]);
                return $journal;
            });
        }
        return $journals;
    }

    /** Intercompany COA — created on demand, matching the spec's chart (§15 assets/liabilities). */
    private function accounts(int $companyId): array
    {
        $mk = fn (string $code, string $name, string $type, string $normal) =>
            ChartOfAccount::firstOrCreate(['company_id' => $companyId, 'code' => $code],
                ['name' => $name, 'type' => $type, 'normal_balance' => $normal,
                 'postable' => true, 'is_active' => true]);
        return [
            $mk('1400', 'Inter-branch Receivable', 'asset', 'debit'),
            $mk('2300', 'Inter-branch Payable', 'liability', 'credit'),
            $mk('4300', 'Inter-branch Handling Recovery', 'revenue', 'credit'),
            $mk('5300', 'Inter-branch Handling Charge', 'expense', 'debit'),
        ];
    }

    private function nextRef(): string
    {
        $p = 'IB-'.now()->format('Y').'-';
        $max = InterBranchEntry::where('reference', 'like', "{$p}%")->lockForUpdate()->max('reference');
        return sprintf('%s%06d', $p, $max ? ((int) substr($max, -6)) + 1 : 1);
    }
}
