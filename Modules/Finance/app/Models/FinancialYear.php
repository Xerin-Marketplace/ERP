<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialYear extends Model
{
    protected $table = 'financial_years';

    protected $fillable = ['company_id','name','starts_on','ends_on','status'];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(\Modules\Organization\Models\Company::class, 'company_id');
    }

    public function periods()
    {
        return $this->hasMany(\Modules\Finance\Models\FinancialPeriod::class, 'year_id');
    }
}
