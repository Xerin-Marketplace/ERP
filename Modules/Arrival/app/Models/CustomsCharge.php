<?php
namespace Modules\Arrival\Models;
use Illuminate\Database\Eloquent\Model;

class CustomsCharge extends Model
{
    protected $table = 'customs_charges';
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['approval_status' => 'pending', 'payment_status' => 'unpaid', 'currency' => 'TZS'];
    public function arrival() { return $this->belongsTo(ShipmentArrival::class, 'arrival_id'); }
    public function customer() { return $this->belongsTo(\Modules\Commercial\Models\Customer::class, 'customer_id'); }
}
