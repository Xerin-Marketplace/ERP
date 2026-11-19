<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class JournalLine extends Model
{
    protected $table = 'journal_lines';

    protected $fillable = ['journal_id','account_id','branch_id','department_id','description','debit','credit'];

    protected $casts = [
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
