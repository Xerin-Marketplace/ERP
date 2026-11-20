<?php
namespace Modules\Shipment\Models;
use Illuminate\Database\Eloquent\Model;

class ShipmentPackage extends Model
{
    protected $table = 'shipment_packages';
    protected $guarded = ['id', 'reference'];
    protected $casts = ['allocated_at' => 'datetime', 'removed_at' => 'datetime'];
    public function package() { return $this->belongsTo(\Modules\Cargo\Models\CargoPackage::class, 'package_id'); }
    public function shipment() { return $this->belongsTo(Shipment::class); }
}
