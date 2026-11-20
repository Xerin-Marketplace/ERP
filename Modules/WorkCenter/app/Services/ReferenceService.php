<?php
namespace Modules\WorkCenter\Services;
use Illuminate\Support\Facades\DB;

/** Sequential corporate references: XER-REP-2026-000001 etc. */
class ReferenceService
{
    public function next(string $prefix, string $table, string $column = 'ref'): string
    {
        $year = now()->year;
        $count = DB::table($table)->where($column, 'like', "{$prefix}-{$year}-%")->count() + 1;
        return sprintf('%s-%d-%06d', $prefix, $year, $count);
    }
}
