<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseClaim extends Model
{
    protected $table = 'expense_claims';

    protected $fillable = ['reference','company_id','employee_id','category_id','branch_id','department_id','expense_date','description','amount','tax_amount','receipt_file_id','status','journal_id','created_by'];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(\Modules\Employee\Models\Employee::class, 'employee_id');
    }

    public function category()
    {
        return $this->belongsTo(\Modules\Finance\Models\ExpenseCategory::class, 'category_id');
    }

    public function journal()
    {
        return $this->belongsTo(\Modules\Finance\Models\Journal::class, 'journal_id');
    }

    public function company()
    {
        return $this->belongsTo(\Modules\Organization\Models\Company::class, 'company_id');
    }
    public function approvals()
    {
        return $this->morphMany(FinanceApproval::class, 'approvable');
    }
}
