<?php
namespace Modules\Delivery\Models;
use Illuminate\Database\Eloquent\Model;

class DeliveryIncident extends Model
{
    protected $table = 'delivery_incidents';
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'open', 'severity' => 'medium'];
    public function order() { return $this->belongsTo(DeliveryOrder::class, 'order_id'); }
}
