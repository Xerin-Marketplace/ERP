<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $table = 'budgets';

    protected $fillable = ['reference','company_id','year_id','branch_id','department_id','name','version','status','control_policy','created_by'];

    protected $casts = [];

    public function lines()
    {
        return $this->hasMany(\Modules\Finance\Models\BudgetLine::class, 'budget_id');
    }

    public function year()
    {
        return $this->belongsTo(\Modules\Finance\Models\FinancialYear::class, 'year_id');
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
