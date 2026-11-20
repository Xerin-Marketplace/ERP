<?php

namespace Modules\Employee\Services;

use Illuminate\Support\Facades\DB;
use Modules\Organization\Models\Branch;

/**
 * Branch-scoped employee numbering — race-safe inside the caller's transaction.
 *
 * Format: EMP-{BRANCH_CODE}-{NNNNNN}  e.g. EMP-TZ-000001, EMP-DXB-000001
 * The last issued row is locked (FOR UPDATE) so concurrent creators serialize;
 * Postgres forbids FOR UPDATE on aggregates, so we lock the row itself.
 */
class EmployeeNumberService
{
    /** Next number for a branch (falls back to the global XERIN sequence). */
    public function next(?Branch $branch = null): string
    {
        if (! $branch) {
            return $this->nextGlobal();
        }

        $code = strtoupper($branch->code);
        $prefix = "EMP-{$code}-";

        $max = DB::table('employees')->where('employee_number', 'like', "{$prefix}%")
            ->orderByDesc('employee_number')->lockForUpdate()->value('employee_number');

        $seq = $max ? ((int) substr($max, -6)) + 1 : 1;

        return sprintf('%s%06d', $prefix, $seq);
    }

    /** Legacy global sequence — kept for company-level / unplaced employees. */
    public function nextGlobal(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $prefix = "XERIN-EMP-{$year}-";

        $max = DB::table('employees')->where('employee_number', 'like', "{$prefix}%")
            ->orderByDesc('employee_number')->lockForUpdate()->value('employee_number');

        $seq = $max ? ((int) substr($max, -6)) + 1 : 1;

        return sprintf('%s%06d', $prefix, $seq);
    }
}
