<?php
namespace Modules\Arrival\Models;
use Illuminate\Database\Eloquent\Model;

class ArrivalEvent extends Model
{
    protected $table = 'arrival_events';
    protected $guarded = ['id', 'reference'];
    protected $casts = ['meta' => 'array'];
    public function arrival() { return $this->belongsTo(ShipmentArrival::class, 'arrival_id'); }
}
