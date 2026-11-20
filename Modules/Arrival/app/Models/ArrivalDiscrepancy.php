<?php
namespace Modules\Arrival\Models;
use Illuminate\Database\Eloquent\Model;

class ArrivalDiscrepancy extends Model
{
    protected $table = 'arrival_discrepancies';
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'open', 'severity' => 'medium'];
    protected $casts = ['resolved_at' => 'datetime'];
    public function arrival() { return $this->belongsTo(ShipmentArrival::class, 'arrival_id'); }
    public function package() { return $this->belongsTo(\Modules\Cargo\Models\CargoPackage::class, 'package_id'); }
}
