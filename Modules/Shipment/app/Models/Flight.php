<?php
namespace Modules\Shipment\Models;
use Illuminate\Database\Eloquent\Model;

class Flight extends Model
{
    protected $table = 'flights';
    protected $guarded = ['id', 'reference'];
    protected $casts = ['scheduled_departure' => 'datetime', 'actual_departure' => 'datetime', 'scheduled_arrival' => 'datetime', 'actual_arrival' => 'datetime'];
    public function events() { return $this->hasMany(FlightEvent::class)->latest(); }
    public function airline() { return $this->belongsTo(Airline::class); }
}
