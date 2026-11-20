<?php
namespace Modules\Delivery\Models;
use Illuminate\Database\Eloquent\Model;

class DriverLocation extends Model
{
    protected $table = 'driver_locations';
    protected $guarded = ['id', 'reference'];
    protected $casts = ['recorded_at' => 'datetime'];
    public function driver() { return $this->belongsTo(Driver::class); }
}
