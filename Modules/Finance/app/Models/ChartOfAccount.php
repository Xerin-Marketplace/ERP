<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    protected $table = 'chart_of_accounts';

    protected $fillable = ['company_id','code','name','description','type','subtype','parent_id','normal_balance','postable','is_active','currency'];

    protected $casts = [
        'postable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(\Modules\Finance\Models\ChartOfAccount::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(\Modules\Finance\Models\ChartOfAccount::class, 'parent_id');
    }

    public function company()
    {
        return $this->belongsTo(\Modules\Organization\Models\Company::class, 'company_id');
    }
}
