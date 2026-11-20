<?php
namespace Modules\Delivery\Models;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrder extends Model
{
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'draft', 'delivery_type' => 'delivery'];
    protected $casts = ['price_breakdown' => 'array', 'scheduled_date' => 'date'];

    public const STATUSES = ['draft','pending_release','ready_for_dispatch','unassigned','assignment_pending','assigned','driver_accepted','driver_rejected','pickup_pending','picked_up','in_transit','arrived_at_destination','delivery_attempted','delivered','failed_delivery','reschedule_requested','rescheduled','return_initiated','returning','returned_to_warehouse','collection_pending','collected_by_customer','cancelled','closed'];
    public const TRANSITIONS = [
        'draft' => ['pending_release','cancelled'],
        'pending_release' => ['ready_for_dispatch','cancelled'],
        'ready_for_dispatch' => ['unassigned','collection_pending'],
        'unassigned' => ['assignment_pending','cancelled'],
        'assignment_pending' => ['assigned','unassigned','cancelled'],
        'assigned' => ['driver_accepted','driver_rejected','unassigned'],
        'driver_accepted' => ['pickup_pending'],
        'driver_rejected' => ['unassigned','assignment_pending'],
        'pickup_pending' => ['picked_up','unassigned'],
        'picked_up' => ['in_transit'],
        'in_transit' => ['arrived_at_destination','failed_delivery','return_initiated'],
        'arrived_at_destination' => ['delivery_attempted','delivered','failed_delivery'],
        'delivery_attempted' => ['delivered','failed_delivery','reschedule_requested'],
        'delivered' => ['closed'],
        'failed_delivery' => ['rescheduled','return_initiated','collection_pending','cancelled'],
        'reschedule_requested' => ['rescheduled','cancelled'],
        'rescheduled' => ['unassigned','collection_pending'],
        'return_initiated' => ['returning'],
        'returning' => ['returned_to_warehouse'],
        'returned_to_warehouse' => ['closed'],
        'collection_pending' => ['collected_by_customer','unassigned'],
        'collected_by_customer' => ['closed'],
        'cancelled' => [], 'closed' => [],
    ];
    public function canTransitionTo(string $to): bool
    { return in_array($to, self::TRANSITIONS[$this->status] ?? [], true); }

    public function packages() { return $this->belongsToMany(\Modules\Cargo\Models\CargoPackage::class, 'delivery_order_packages', 'order_id', 'package_id')->wherePivotNull('removed_at')->withTimestamps(); }
    public function activePackages() { return $this->hasMany(DeliveryOrderPackage::class, 'order_id')->whereNull('removed_at'); }
    public function events() { return $this->hasMany(DeliveryEvent::class, 'order_id')->latest(); }
    public function assignments() { return $this->hasMany(DeliveryAssignment::class, 'order_id')->latest(); }
    public function attempts() { return $this->hasMany(DeliveryAttempt::class, 'order_id')->latest(); }
    public function pod() { return $this->hasOne(ProofOfDelivery::class, 'order_id'); }
    public function returns() { return $this->hasMany(DeliveryReturn::class, 'order_id'); }
    public function collection() { return $this->hasOne(CustomerCollection::class, 'order_id'); }
    public function cod() { return $this->hasMany(CodCollection::class, 'order_id'); }
    public function customer() { return $this->belongsTo(\Modules\Commercial\Models\Customer::class); }
    public function driver() { return $this->belongsTo(Driver::class); }
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function service() { return $this->belongsTo(DeliveryService::class, 'service_id'); }
    public function zone() { return $this->belongsTo(DeliveryZone::class, 'zone_id'); }
    public function batch() { return $this->belongsTo(\Modules\Arrival\Models\DistributionBatch::class, 'batch_id'); }
    public function scopeFor($q, $u) { return $u->can('delivery.all_branches') || ! $u->currentAssignment?->branch_id ? $q : $q->where('branch_id', $u->currentAssignment->branch_id); }
}
