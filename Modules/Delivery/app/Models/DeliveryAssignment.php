<?php
namespace Modules\Delivery\Models;
use Illuminate\Database\Eloquent\Model;

class DeliveryAssignment extends Model
{
    protected $table = 'delivery_assignments';
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'offered'];
    protected $casts = ['offered_at' => 'datetime', 'responded_at' => 'datetime'];
    public function order() { return $this->belongsTo(DeliveryOrder::class, 'order_id'); }
    public function driver() { return $this->belongsTo(Driver::class); }
}
