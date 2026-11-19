<?php

namespace Modules\Finance\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Models\AssetDepreciationSchedule;
use Modules\Finance\Models\FixedAsset;

class FixedAssetService
{
    public function __construct(
        private FinNumberService $numbers,
        private JournalService $journals,
        private FinancePeriodService $periods,
        private AuditService $audit,
    ) {}

    public function register(array $data): FixedAsset
    {
        return DB::transaction(function () use ($data) {
            $asset = FixedAsset::create([
                'reference' => $this->numbers->for('asset'),
                'company_id' => $data['company_id'],
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'custodian_employee_id' => $data['custodian_employee_id'] ?? null,
                'acquired_on' => $data['acquired_on'],
                'cost' => $data['cost'],
                'residual_value' => $data['residual_value'] ?? 0,
                'status' => 'active',
            ]);
            $this->buildSchedule($asset);
            $this->audit->log('finance.asset.registered', $asset, new: ['cost' => (float) $asset->cost]);
            return $asset;
        });
    }

    /** Straight-line or declining-balance monthly schedule over useful life. */
    private function buildSchedule(FixedAsset $asset): void
    {
        $category = $asset->category;
        $months = max(1, $category->useful_life_months);
        $depreciable = (float) $asset->cost - (float) $asset->residual_value;
        if ($depreciable <= 0) return;

        $periods = \Modules\Finance\Models\FinancialPeriod::where('company_id', $asset->company_id)
            ->whereDate('ends_on', '>=', $asset->acquired_on)->orderBy('starts_on')->limit($months)->get();

        $bookValue = (float) $asset->cost;
        foreach ($periods as $period) {
            if ($asset->accumulated_depreciation >= $depreciable) break;
            if ($category->method === 'declining_balance' && $category->declining_rate) {
                $amount = round($bookValue * ((float) $category->declining_rate / 100) / 12, 2);
            } else {
                $amount = round($depreciable / $months, 2);
            }
            $amount = min($amount, $depreciable - (float) $asset->accumulated_depreciation);
            AssetDepreciationSchedule::create([
                'asset_id' => $asset->id, 'period_id' => $period->id,
                'amount' => $amount, 'status' => 'scheduled',
            ]);
            $bookValue -= $amount;
        }
    }

    /**
     * Post one period's depreciation. Dr depreciation expense, Cr accumulated
     * depreciation. unique(asset_id, period_id) makes double-posting impossible.
     */
    public function postDepreciation(AssetDepreciationSchedule $schedule, int $userId): AssetDepreciationSchedule
    {
        return DB::transaction(function () use ($schedule, $userId) {
            $schedule = AssetDepreciationSchedule::whereKey($schedule->id)->lockForUpdate()->firstOrFail();
            if ($schedule->status === 'posted') {
                throw ValidationException::withMessages(['depreciation' => 'Already posted for this period.']);
            }
            $asset = $schedule->asset;
            $category = $asset->category;
            if (! $category->depreciation_account_id || ! $category->accumulated_account_id) {
                throw ValidationException::withMessages(['category' => 'Category missing depreciation/accumulated accounts.']);
            }
            $journal = $this->journals->create([
                'company_id' => $asset->company_id, 'period_id' => $schedule->period_id,
                'type' => 'depreciation', 'journal_date' => $schedule->period->ends_on->toDateString(),
                'memo' => "Depreciation {$asset->reference} {$schedule->period->name}",
                'source_type' => 'asset_depreciation', 'source_id' => $schedule->id,
                'lines' => [
                    ['account_id' => $category->depreciation_account_id, 'debit' => (float) $schedule->amount, 'credit' => 0],
                    ['account_id' => $category->accumulated_account_id, 'debit' => 0, 'credit' => (float) $schedule->amount],
                ],
            ], $userId);
            $this->journals->post($journal, $userId);

            $schedule->update(['status' => 'posted', 'journal_id' => $journal->id]);
            $asset->increment('accumulated_depreciation', $schedule->amount);
            $this->audit->log('finance.asset.depreciated', $asset, new: ['amount' => (float) $schedule->amount]);
            return $schedule->fresh();
        });
    }
}
