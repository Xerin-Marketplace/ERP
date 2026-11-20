<?php
namespace Modules\Shipment\Models;
use Illuminate\Database\Eloquent\Model;

class AirlineHandover extends Model
{
    protected $table = 'airline_handovers';
    protected $guarded = ['id', 'reference'];
    protected $casts = ['rejected_packages' => 'array', 'missing_packages' => 'array'];
    public function shipment() { return $this->belongsTo(Shipment::class); }
}
