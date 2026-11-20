<?php
namespace Modules\Cargo\Models;
use Illuminate\Database\Eloquent\Model;

class CargoPackage extends Model
{
    protected $table = 'cargo_packages';
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'received', 'inspection_status' => 'pending', 'packaging_status' => 'original'];
    protected $casts = ['unidentified' => 'boolean'];
    public const STATUSES = ['expected','received','identified','weighed','inspection_passed','on_hold','stored','ready','allocated','dispatched','cancelled'];
    public function receiving() { return $this->belongsTo(Receiving::class, 'receiving_id'); }
    public function booking() { return $this->belongsTo(CargoBooking::class, 'booking_id'); }
    public function customer() { return $this->belongsTo(\Modules\Commercial\Models\Customer::class, 'customer_id'); }
    public function branch() { return $this->belongsTo(\Modules\Organization\Models\Branch::class); }
    public function location() { return $this->belongsTo(WarehouseLocation::class, 'location_id'); }
    public function measurements() { return $this->hasMany(Measurement::class, 'package_id')->latest(); }
    public function inspections() { return $this->hasMany(Inspection::class, 'package_id')->latest(); }
    public function repackagings() { return $this->hasMany(Repackaging::class, 'package_id')->latest(); }
    public function events() { return $this->hasMany(CargoEvent::class, 'package_id')->latest(); }
    public function movements() { return $this->hasMany(WarehouseMovement::class, 'package_id')->latest(); }
    public function scopeFor($q, $u) { return $u->can('cargo.all_branches') || ! $u->currentAssignment?->branch_id ? $q : $q->where('branch_id', $u->currentAssignment->branch_id); }
    public function onHold(): bool { return $this->status === 'on_hold'; }
}
