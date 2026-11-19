<?php

namespace Modules\Employee\Services;

use Illuminate\Support\Facades\DB;

/**
 * XERIN-EMP-YYYY-000001 — race-safe inside the caller's transaction.
 */
class EmployeeNumberService
{
    public function next(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $prefix = "XERIN-EMP-{$year}-";

        $max = DB::table('employees')->where('employee_number', 'like', "{$prefix}%")
            ->lockForUpdate()->max('employee_number');

        $seq = $max ? ((int) substr($max, -6)) + 1 : 1;

        return sprintf('%s%06d', $prefix, $seq);
    }
}
