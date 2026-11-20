<?php
namespace Modules\Shipment\Models;
use Illuminate\Database\Eloquent\Model;

class ShipmentEvent extends Model
{
    protected $table = 'shipment_events';
    protected $guarded = ['id', 'reference'];
    protected $casts = ['meta' => 'array'];
    public function shipment() { return $this->belongsTo(Shipment::class); }
}
