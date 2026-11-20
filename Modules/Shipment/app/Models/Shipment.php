<?php
namespace Modules\Shipment\Models;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'draft'];
    protected $casts = ['planned_departure' => 'date', 'planned_arrival' => 'date',
        'actual_departure' => 'datetime', 'actual_arrival' => 'datetime'];

    public const TRANSITIONS = [
        'draft' => ['planning','cancelled'],
        'planning' => ['pending_approval','ready_for_booking','cancelled'],
        'pending_approval' => ['ready_for_booking','planning','cancelled'],
        'ready_for_booking' => ['booking_pending','cancelled'],
        'booking_pending' => ['booked','ready_for_booking','cancelled'],
        'booked' => ['documentation_pending','cancelled'],
        'documentation_pending' => ['documentation_complete','cancelled'],
        'documentation_complete' => ['ready_for_dispatch','cancelled'],
        'ready_for_dispatch' => ['dispatched_to_airport','cancelled'],
        'dispatched_to_airport' => ['airline_handover_pending','ready_for_dispatch'],
        'airline_handover_pending' => ['handed_over','dispatched_to_airport'],
        'handed_over' => ['manifest_confirmed','departed'],
        'manifest_confirmed' => ['departed'],
        'departed' => ['in_transit','delayed','offloaded','transshipment'],
        'in_transit' => ['arrived','delayed','transshipment','offloaded'],
        'delayed' => ['in_transit','arrived','cancelled'],
        'transshipment' => ['in_transit','arrived','delayed'],
        'offloaded' => ['planning','cancelled'],
        'arrived' => ['closed'],
        'cancelled' => [], 'closed' => [],
    ];
    public const STATUSES = ['draft','planning','pending_approval','ready_for_booking','booking_pending','booked','documentation_pending','documentation_complete','ready_for_dispatch','dispatched_to_airport','airline_handover_pending','handed_over','manifest_confirmed','departed','in_transit','delayed','offloaded','transshipment','arrived','cancelled','closed'];

    public function canTransitionTo(string $to): bool
    { return in_array($to, self::TRANSITIONS[$this->status] ?? [], true); }

    public function packages() { return $this->belongsToMany(\Modules\Cargo\Models\CargoPackage::class, 'shipment_packages', 'shipment_id', 'package_id')->wherePivotNull('removed_at')->withPivot('allocated_at')->withTimestamps(); }
    public function allocations() { return $this->hasMany(ShipmentPackage::class); }
    public function activeAllocations() { return $this->hasMany(ShipmentPackage::class)->whereNull('removed_at'); }
    public function events() { return $this->hasMany(ShipmentEvent::class)->latest(); }
    public function bookings() { return $this->hasMany(AirlineBooking::class)->latest(); }
    public function waybills() { return $this->hasMany(AirWaybill::class); }
    public function documents() { return $this->hasMany(ShipmentDocument::class)->latest(); }
    public function manifests() { return $this->hasMany(CargoManifest::class)->latest(); }
    public function dispatches() { return $this->hasMany(AirportDispatch::class)->latest(); }
    public function handovers() { return $this->hasMany(AirlineHandover::class)->latest(); }
    public function exceptions() { return $this->hasMany(ShipmentException::class)->latest(); }
    public function airline() { return $this->belongsTo(Airline::class); }
    public function originBranch() { return $this->belongsTo(\Modules\Organization\Models\Branch::class, 'origin_branch_id'); }
    public function destinationBranch() { return $this->belongsTo(\Modules\Organization\Models\Branch::class, 'destination_branch_id'); }
    public function parent() { return $this->belongsTo(Shipment::class, 'parent_id'); }
    public function splits() { return $this->hasMany(Shipment::class, 'parent_id'); }
    public function scopeFor($q, $u) { return $u->can('shipment.all_branches') || ! $u->currentAssignment?->branch_id ? $q : $q->where('origin_branch_id', $u->currentAssignment->branch_id); }
    public function recalcTotals(): void
    {
        $pkgs = $this->packages();
        $this->update(['total_packages' => $pkgs->count(),
            'total_gross_weight' => $pkgs->sum('gross_weight'),
            'total_volumetric_weight' => $pkgs->sum('volumetric_weight'),
            'total_chargeable_weight' => $pkgs->sum('chargeable_weight')]);
    }
}
