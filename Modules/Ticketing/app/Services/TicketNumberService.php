<?php

namespace Modules\Ticketing\Services;

use Illuminate\Support\Facades\DB;

/** XERIN-TKT-YYYY-###### / XERIN-TSK-YYYY-###### — race-safe inside transactions. */
class TicketNumberService
{
    public function ticket(?int $year = null): string
    {
        return $this->next('tickets', 'ticket_number', 'XERIN-TKT', $year);
    }

    public function task(?int $year = null): string
    {
        return $this->next('ticket_tasks', 'task_number', 'XERIN-TSK', $year);
    }

    /**
     * Branch-scoped IT ticket number: IT-{BRANCHCODE}-{YYYY}-{######}
     * e.g. IT-TZ-2026-000001, IT-DXB-2026-000001
     */
    public function itTicket(string $branchCode, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $full = 'IT-'.strtoupper($branchCode)."-{$year}-";
        $max = DB::table('tickets')->where('ticket_number', 'like', "{$full}%")->orderByDesc('ticket_number')->lockForUpdate()->value('ticket_number');
        $seq = $max ? ((int) substr($max, -6)) + 1 : 1;

        return sprintf('%s%06d', $full, $seq);
    }

    private function next(string $table, string $column, string $prefix, ?int $year): string
    {
        $year ??= (int) now()->format('Y');
        $full = "{$prefix}-{$year}-";
        $max = DB::table($table)->where($column, 'like', "{$full}%")->orderByDesc($column)->lockForUpdate()->value($column);
        $seq = $max ? ((int) substr($max, -6)) + 1 : 1;

        return sprintf('%s%06d', $full, $seq);
    }
}
