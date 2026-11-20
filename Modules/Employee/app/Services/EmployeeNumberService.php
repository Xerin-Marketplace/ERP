<?php

namespace Modules\Employee\Services;

use Illuminate\Support\Facades\DB;
use Modules\Organization\Models\Branch;

/**
 * Branch-scoped employee numbering — race-safe inside the caller's transaction.
 *
 * Format: EMP-{BRANCH_CODE}-{NNNNNN}  e.g. EMP-TZ-000001, EMP-DXB-000001
 * Each branch keeps its own sequence; codes are globally unique branches codes,
 * so generated numbers cannot collide across branches.
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
            ->lockForUpdate()->max('employee_number');

        $seq = $max ? ((int) substr($max, -6)) + 1 : 1;

        return sprintf('%s%06d', $prefix, $seq);
    }

    /** Legacy global sequence — kept for company-level / unplaced employees. */
    public function nextGlobal(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $prefix = "XERIN-EMP-{$year}-";

        $max = DB::table('employees')->where('employee_number', 'like', "{$prefix}%")
            ->lockForUpdate()->max('employee_number');

        $seq = $max ? ((int) substr($max, -6)) + 1 : 1;

        return sprintf('%s%06d', $prefix, $seq);
    }
}
