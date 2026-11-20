<?php
namespace Modules\Delivery\Models;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrderPackage extends Model
{
    protected $table = 'delivery_order_packages';
    protected $guarded = ['id', 'reference'];
    protected $casts = ['added_at' => 'datetime', 'removed_at' => 'datetime'];
    public function order() { return $this->belongsTo(DeliveryOrder::class, 'order_id'); }
    public function package() { return $this->belongsTo(\Modules\Cargo\Models\CargoPackage::class, 'package_id'); }
}
