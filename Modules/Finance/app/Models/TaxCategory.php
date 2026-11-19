<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class TaxCategory extends Model
{
    protected $table = 'tax_categories';

    protected $fillable = ['company_id','code','name','account_id'];

    protected $casts = [];

    public function account()
    {
        return $this->belongsTo(\Modules\Finance\Models\ChartOfAccount::class, 'account_id');
    }
}
