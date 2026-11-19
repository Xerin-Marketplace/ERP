<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class FixedAsset extends Model
{
    protected $table = 'fixed_assets';

    protected $fillable = ['reference','company_id','category_id','name','supplier_id','branch_id','department_id','custodian_employee_id','acquired_on','cost','residual_value','accumulated_depreciation','status'];

    protected $casts = [
        'acquired_on' => 'date',
        'cost' => 'decimal:2',
        'residual_value' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(\Modules\Finance\Models\AssetCategory::class, 'category_id');
    }

    public function schedules()
    {
        return $this->hasMany(\Modules\Finance\Models\AssetDepreciationSchedule::class, 'asset_id');
    }

    public function custodian()
    {
        return $this->belongsTo(\Modules\Employee\Models\Employee::class, 'custodian_employee_id');
    }

    public function company()
    {
        return $this->belongsTo(\Modules\Organization\Models\Company::class, 'company_id');
    }
}
