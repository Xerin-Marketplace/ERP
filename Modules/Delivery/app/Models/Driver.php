<?php
namespace Modules\Delivery\Models;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $table = 'drivers';
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['availability' => 'offline', 'verification_status' => 'pending'];
    protected $casts = ['zones' => 'array', 'license_expiry' => 'date', 'last_active_at' => 'datetime'];
    public function assignments() { return $this->hasMany(DeliveryAssignment::class, 'driver_id'); }
    public function locations() { return $this->hasMany(DriverLocation::class, 'driver_id')->latest('recorded_at'); }
    public function latestLocation() { return $this->hasOne(DriverLocation::class, 'driver_id')->latestOfMany('recorded_at'); }
    public function isEligible(): bool
    { return $this->verification_status === 'verified' && in_array($this->availability, ['available','busy'], true)
        && (! $this->license_expiry || $this->license_expiry->isFuture()); }
}
