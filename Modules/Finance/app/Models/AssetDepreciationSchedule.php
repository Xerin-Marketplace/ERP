<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class AssetDepreciationSchedule extends Model
{
    protected $table = 'asset_depreciation_schedules';

    protected $fillable = ['asset_id','period_id','amount','status','journal_id'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function asset()
    {
        return $this->belongsTo(\Modules\Finance\Models\FixedAsset::class, 'asset_id');
    }

    public function period()
    {
        return $this->belongsTo(\Modules\Finance\Models\FinancialPeriod::class, 'period_id');
    }

    public function journal()
    {
        return $this->belongsTo(\Modules\Finance\Models\Journal::class, 'journal_id');
    }
}
