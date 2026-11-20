<?php
namespace Modules\Delivery\Models;
use Illuminate\Database\Eloquent\Model;

class CustomerCollection extends Model
{
    protected $table = 'customer_collections';
    protected $guarded = ['id', 'reference'];
    protected $casts = ['package_ids' => 'array', 'collected_at' => 'datetime'];
    public function order() { return $this->belongsTo(DeliveryOrder::class, 'order_id'); }
}
