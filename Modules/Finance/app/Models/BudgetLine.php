<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetLine extends Model
{
    protected $table = 'budget_lines';

    protected $fillable = ['budget_id','account_id','amount','consumed'];

    protected $casts = [
        'amount' => 'decimal:2',
        'consumed' => 'decimal:2',
    ];

    public function budget()
    {
        return $this->belongsTo(\Budget::class, 'budget_id');
    }

    public function account()
    {
        return $this->belongsTo(\Modules\Finance\Models\ChartOfAccount::class, 'account_id');
    }
}
