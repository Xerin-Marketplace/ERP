<?php
namespace Modules\Shipment\Models;
use Illuminate\Database\Eloquent\Model;

class ShipmentException extends Model
{
    protected $table = 'shipment_exceptions';
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'open', 'severity' => 'medium'];
    protected $casts = ['package_ids' => 'array', 'resolved_at' => 'datetime'];
    public const CATEGORIES = ['booking_rejected','flight_cancelled','flight_delayed','offloaded','missing_packages','damaged','manifest_mismatch','awb_discrepancy','document_rejected','handover_failed','route_change','transshipment'];
    public function shipment() { return $this->belongsTo(Shipment::class); }
}
