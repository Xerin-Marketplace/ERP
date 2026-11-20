<?php
namespace Modules\Delivery\Models;
use Illuminate\Database\Eloquent\Model;

class ProofOfDelivery extends Model
{
    protected $table = 'proof_of_deliveries';
    protected $guarded = ['id', 'reference'];
    protected $casts = ['delivered_at' => 'datetime', 'scanned_package_ids' => 'array'];
    public function order() { return $this->belongsTo(DeliveryOrder::class, 'order_id'); }
}
