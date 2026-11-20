<?php
namespace Modules\Cargo\Models;
use Illuminate\Database\Eloquent\Model;
class WarehouseMovement extends Model
{
    protected $guarded = ['id', 'reference'];
    public function package() { return $this->belongsTo(CargoPackage::class, 'package_id'); }
    public function from() { return $this->belongsTo(WarehouseLocation::class, 'from_location_id'); }
    public function to() { return $this->belongsTo(WarehouseLocation::class, 'to_location_id'); }
    public function mover() { return $this->belongsTo(\App\Models\User::class, 'moved_by'); }
}
