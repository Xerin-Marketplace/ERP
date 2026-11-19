<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;

/** Race-safe reference generation: XERIN-<PREFIX>-YYYY-###### inside transactions. */
class FinNumberService
{
    private const MAP = [
        'journal'        => ['journals', 'reference', 'XERIN-JRN'],
        'supplier'       => ['suppliers', 'code', 'XERIN-SUP'],
        'supplier_invoice' => ['supplier_invoices', 'reference', 'XERIN-SINV'],
        'customer'       => ['customers', 'code', 'XERIN-CUS'],
        'customer_invoice' => ['customer_invoices', 'reference', 'XERIN-CINV'],
        'payment'        => ['payment_records', 'reference', 'XERIN-PAY'],
        'expense'        => ['expense_claims', 'reference', 'XERIN-EXP'],
        'reconciliation' => ['bank_reconciliations', 'reference', 'XERIN-REC'],
        'budget'         => ['budgets', 'reference', 'XERIN-BGT'],
        'asset'          => ['fixed_assets', 'reference', 'XERIN-AST'],
    ];

    public function for(string $kind, ?int $year = null): string
    {
        [$table, $column, $prefix] = self::MAP[$kind];
        $year ??= (int) now()->format('Y');
        $full = "{$prefix}-{$year}-";
        $max = DB::table($table)->where($column, 'like', "{$full}%")->lockForUpdate()->max($column);
        $seq = $max ? ((int) substr($max, -6)) + 1 : 1;
        return sprintf('%s%06d', $full, $seq);
    }
}
