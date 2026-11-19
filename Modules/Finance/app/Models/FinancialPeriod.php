<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialPeriod extends Model
{
    protected $table = 'financial_periods';

    protected $fillable = ['year_id','company_id','name','sequence','starts_on','ends_on','status','closed_by','closed_at'];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'closed_at' => 'datetime',
    ];

    public function year()
    {
        return $this->belongsTo(\Modules\Finance\Models\FinancialYear::class, 'year_id');
    }

    public function company()
    {
        return $this->belongsTo(\Modules\Organization\Models\Company::class, 'company_id');
    }
}
