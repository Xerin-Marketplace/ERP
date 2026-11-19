<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $table = 'exchange_rates';

    protected $fillable = ['from_currency','to_currency','rate','effective_on','source'];

    protected $casts = [
        'effective_on' => 'date',
        'rate' => 'decimal:8',
    ];
}
