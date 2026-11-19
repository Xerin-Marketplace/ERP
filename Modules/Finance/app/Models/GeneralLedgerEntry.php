<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralLedgerEntry extends Model
{
    protected $table = 'general_ledger_entries';

    protected $fillable = ['company_id','period_id','journal_id','journal_line_id','account_id','branch_id','department_id','entry_date','debit','credit','reference','description'];

    protected $casts = [
        'entry_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function journal()
    {
        return $this->belongsTo(\Modules\Finance\Models\Journal::class, 'journal_id');
    }

    public function account()
    {
        return $this->belongsTo(\Modules\Finance\Models\ChartOfAccount::class, 'account_id');
    }
}
