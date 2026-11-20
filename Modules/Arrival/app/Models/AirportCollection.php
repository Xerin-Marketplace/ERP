<?php
namespace Modules\Arrival\Models;
use Illuminate\Database\Eloquent\Model;

class AirportCollection extends Model
{
    protected $table = 'airport_collections';
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'requested'];
    protected $casts = ['scanned_package_ids' => 'array', 'approved_at' => 'datetime', 'collected_at' => 'datetime'];
    public function arrival() { return $this->belongsTo(ShipmentArrival::class, 'arrival_id'); }
    public function agent() { return $this->belongsTo(ClearingAgent::class, 'agent_id'); }
}
