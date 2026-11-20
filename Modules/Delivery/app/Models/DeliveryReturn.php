<?php
namespace Modules\Delivery\Models;
use Illuminate\Database\Eloquent\Model;

class DeliveryReturn extends Model
{
    protected $table = 'delivery_returns';
    protected $guarded = ['id', 'reference'];
    protected $casts = ['package_ids' => 'array', 'received_at' => 'datetime'];
    public function order() { return $this->belongsTo(DeliveryOrder::class, 'order_id'); }
}
