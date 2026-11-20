<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class EfdReceipt extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['payload' => 'array', 'response' => 'array',
        'queued_at' => 'datetime', 'transmitted_at' => 'datetime'];

    public function invoice() { return $this->belongsTo(CustomerInvoice::class, 'customer_invoice_id'); }
    public function branch() { return $this->belongsTo(\Modules\Organization\Models\Branch::class); }
    public function creator() { return $this->belongsTo(\App\Models\User::class, 'created_by'); }
}
