<?php

namespace Modules\HR\Services;

use Illuminate\Support\Facades\DB;

/** Race-safe reference generation: XERIN-<PREFIX>-YYYY-###### inside transactions. */
class HRNumberService
{
    private const MAP = [
        'attendance'   => ['attendance_records', 'reference', 'XERIN-ATT'],
        'correction'   => ['attendance_corrections', 'reference', 'XERIN-ACR'],
        'leave'        => ['leave_requests', 'reference', 'XERIN-LEV'],
        'overtime'     => ['overtime_requests', 'reference', 'XERIN-OVT'],
        'requisition'  => ['recruitment_requisitions', 'reference', 'XERIN-REQ'],
        'vacancy'      => ['job_vacancies', 'reference', 'XERIN-VAC'],
        'application'  => ['recruitment_applications', 'reference', 'XERIN-APP'],
        'review'       => ['performance_reviews', 'reference', 'XERIN-PRF'],
        'case'         => ['disciplinary_cases', 'reference', 'XERIN-CAS'],
        'welfare'      => ['welfare_records', 'reference', 'XERIN-WEL'],
        'document'     => ['hr_documents', 'reference', 'XERIN-DOC'],
    ];

    public function for(string $kind, ?int $year = null): string
    {
        [$table, $column, $prefix] = self::MAP[$kind];
        $year ??= (int) now()->format('Y');
        $full = "{$prefix}-{$year}-";
        $max = DB::table($table)->where($column, 'like', "{$full}%")->orderByDesc($column)->lockForUpdate()->value($column);
        $seq = $max ? ((int) substr($max, -6)) + 1 : 1;

        return sprintf('%s%06d', $full, $seq);
    }
}
