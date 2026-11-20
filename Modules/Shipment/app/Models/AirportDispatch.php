<?php
namespace Modules\Shipment\Models;
use Illuminate\Database\Eloquent\Model;

class AirportDispatch extends Model
{
    protected $table = 'airport_dispatches';
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'scheduled'];
    protected $casts = ['departed_at' => 'datetime', 'arrived_at' => 'datetime'];
    public function shipment() { return $this->belongsTo(Shipment::class); }
}
