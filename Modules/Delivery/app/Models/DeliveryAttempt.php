<?php
namespace Modules\Delivery\Models;
use Illuminate\Database\Eloquent\Model;

class DeliveryAttempt extends Model
{
    protected $table = 'delivery_attempts';
    protected $guarded = ['id', 'reference'];
    public const REASONS = ['customer_unavailable','wrong_address','unreachable','refused','access_restricted','payment_pending','damaged','vehicle_failure','driver_incident','other'];
    public function order() { return $this->belongsTo(DeliveryOrder::class, 'order_id'); }
}
