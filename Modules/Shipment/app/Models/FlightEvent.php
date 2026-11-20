<?php
namespace Modules\Shipment\Models;
use Illuminate\Database\Eloquent\Model;

class FlightEvent extends Model
{
    protected $table = 'flight_events';
    protected $guarded = ['id', 'reference'];
    protected $casts = ['meta' => 'array'];
    public function flight() { return $this->belongsTo(Flight::class); }
}
