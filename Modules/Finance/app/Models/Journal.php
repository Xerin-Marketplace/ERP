<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    protected $table = 'journals';

    protected $fillable = ['reference','company_id','period_id','type','journal_date','currency','exchange_rate','memo','status','source_type','source_id','reversal_of_id','reversed_by_id','created_by','submitted_by','approved_by','posted_by','rejected_by','submitted_at','approved_at','posted_at','rejection_reason'];

    protected $casts = [
        'journal_date' => 'date',
        'exchange_rate' => 'decimal:8',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(\Modules\Finance\Models\JournalLine::class, 'journal_id');
    }

    public function period()
    {
        return $this->belongsTo(\Modules\Finance\Models\FinancialPeriod::class, 'period_id');
    }

    public function company()
    {
        return $this->belongsTo(\Modules\Organization\Models\Company::class, 'company_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
