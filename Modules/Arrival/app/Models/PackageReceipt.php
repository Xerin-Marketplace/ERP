<?php
namespace Modules\Arrival\Models;
use Illuminate\Database\Eloquent\Model;

class PackageReceipt extends Model
{
    protected $table = 'package_receipts';
    protected $guarded = ['id', 'reference'];
    protected $casts = ['received_at' => 'datetime'];
    public function package() { return $this->belongsTo(\Modules\Cargo\Models\CargoPackage::class, 'package_id'); }
    public function arrival() { return $this->belongsTo(ShipmentArrival::class, 'arrival_id'); }
    public function location() { return $this->belongsTo(\Modules\Cargo\Models\WarehouseLocation::class, 'location_id'); }
}
