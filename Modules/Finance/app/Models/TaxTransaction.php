<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class TaxTransaction extends Model
{
    protected $table = 'tax_transactions';

    protected $fillable = ['company_id','tax_rate_id','document_type','document_id','base_amount','tax_amount','rate_applied'];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'rate_applied' => 'decimal:4',
    ];

    public function rate()
    {
        return $this->belongsTo(\Modules\Finance\Models\TaxRate::class, 'tax_rate_id');
    }
}
