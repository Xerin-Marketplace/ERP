<?php

namespace Modules\Finance\Services;

use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Models\FinancialPeriod;
use Modules\Finance\Models\FinancialYear;

class FinancePeriodService
{
    public function __construct(private AuditService $audit) {}

    /** Create a financial year with 12 monthly periods. */
    public function createYear(int $companyId, string $name, string $startsOn): FinancialYear
    {
        return DB::transaction(function () use ($companyId, $name, $startsOn) {
            $start = Carbon::parse($startsOn)->startOfMonth();
            $year = FinancialYear::create([
                'company_id' => $companyId,
                'name'       => $name,
                'starts_on'  => $start->toDateString(),
                'ends_on'    => $start->copy()->addYear()->subDay()->toDateString(),
                'status'     => 'open',
            ]);
            for ($i = 0; $i < 12; $i++) {
                $m = $start->copy()->addMonths($i);
                $year->periods()->create([
                    'company_id' => $companyId,
                    'name'       => $m->format('M Y'),
                    'sequence'   => $i + 1,
                    'starts_on'  => $m->copy()->startOfMonth()->toDateString(),
                    'ends_on'    => $m->copy()->endOfMonth()->toDateString(),
                    'status'     => 'open',
                ]);
            }
            $this->audit->log('finance.year.created', $year, new: ['periods' => 12]);
            return $year;
        });
    }

    public function close(FinancialPeriod $period, int $userId): FinancialPeriod
    {
        if ($period->status !== 'open') {
            throw ValidationException::withMessages(['period' => 'Only open periods can be closed.']);
        }
        $period->update(['status' => 'closed', 'closed_by' => $userId, 'closed_at' => now()]);
        $this->audit->log('finance.period.closed', $period);
        return $period;
    }

    /** Reopening a closed period requires explicit authorized action — logged. */
    public function reopen(FinancialPeriod $period, int $userId): FinancialPeriod
    {
        if ($period->status === 'locked') {
            throw ValidationException::withMessages(['period' => 'Locked periods cannot be reopened.']);
        }
        $period->update(['status' => 'open', 'closed_by' => null, 'closed_at' => null]);
        $this->audit->log('finance.period.reopened', $period);
        return $period;
    }

    public function lock(FinancialPeriod $period, int $userId): FinancialPeriod
    {
        $period->update(['status' => 'locked', 'closed_by' => $userId, 'closed_at' => now()]);
        $this->audit->log('finance.period.locked', $period);
        return $period;
    }

    public function openPeriodFor(int $companyId, string $date): ?FinancialPeriod
    {
        return FinancialPeriod::where('company_id', $companyId)
            ->where('status', 'open')
            ->whereDate('starts_on', '<=', $date)
            ->whereDate('ends_on', '>=', $date)
            ->first();
    }
}
