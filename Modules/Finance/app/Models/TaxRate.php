<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    protected $table = 'tax_rates';

    protected $fillable = ['category_id','name','rate','inclusive','effective_from','effective_to','is_active'];

    protected $casts = [
        'rate' => 'decimal:4',
        'inclusive' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(\Modules\Finance\Models\TaxCategory::class, 'category_id');
    }
}
