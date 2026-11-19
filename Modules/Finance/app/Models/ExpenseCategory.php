<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    protected $table = 'expense_categories';

    protected $fillable = ['company_id','code','name','account_id','approval_threshold','is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'approval_threshold' => 'decimal:2',
    ];

    public function account()
    {
        return $this->belongsTo(\Modules\Finance\Models\ChartOfAccount::class, 'account_id');
    }
}
