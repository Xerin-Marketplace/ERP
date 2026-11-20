<?php
namespace Modules\Shipment\Models;
use Illuminate\Database\Eloquent\Model;

class AirlineBooking extends Model
{
    protected $table = 'airline_bookings';
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'draft'];
    public function shipment() { return $this->belongsTo(Shipment::class); }
    public function airline() { return $this->belongsTo(Airline::class); }
}
