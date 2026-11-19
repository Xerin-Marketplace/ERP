<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    protected $table = 'payment_allocations';

    protected $fillable = ['payment_id','document_type','document_id','amount'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payment()
    {
        return $this->belongsTo(\Modules\Finance\Models\PaymentRecord::class, 'payment_id');
    }
}
