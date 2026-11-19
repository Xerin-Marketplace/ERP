<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    protected $table = 'asset_categories';

    protected $fillable = ['company_id','code','name','asset_account_id','depreciation_account_id','accumulated_account_id','useful_life_months','method','declining_rate'];

    protected $casts = [
        'declining_rate' => 'decimal:4',
    ];

    public function assets()
    {
        return $this->hasMany(\Modules\Finance\Models\FixedAsset::class, 'category_id');
    }
}
