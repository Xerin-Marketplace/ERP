<?php
namespace Modules\Delivery\Models;
use Illuminate\Database\Eloquent\Model;

class DeliveryEvent extends Model
{
    protected $table = 'delivery_events';
    protected $guarded = ['id', 'reference'];
    protected $casts = ['meta' => 'array'];
    public function order() { return $this->belongsTo(DeliveryOrder::class, 'order_id'); }
}
