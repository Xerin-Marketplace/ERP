<?php

namespace Modules\Commercial\Services;

use Illuminate\Support\Facades\DB;

/** Race-safe XER-XXX-YYYY-###### references (same convention as TicketNumberService). */
class SequenceService
{
    public function customer(): string { return $this->next('customers', 'code', 'XER-CUS'); }
    public function lead(): string { return $this->next('leads', 'reference', 'XER-LED'); }
    public function inquiry(): string { return $this->next('inquiries', 'reference', 'XER-INQ'); }
    public function quotation(): string { return $this->next('quotations', 'number', 'XER-QUO'); }
    public function handover(): string { return $this->next('commercial_handovers', 'reference', 'XER-HND'); }

    private function next(string $table, string $col, string $prefix): string
    {
        $year = now()->format('Y');
        $full = "{$prefix}-{$year}-";
        $max = DB::table($table)->where($col, 'like', "{$full}%")->orderByDesc($col)->lockForUpdate()->value($col);
        return sprintf('%s%06d', $full, $max ? ((int) substr($max, -6)) + 1 : 1);
    }
}
