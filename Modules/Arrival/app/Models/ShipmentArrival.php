<?php
namespace Modules\Arrival\Models;
use Illuminate\Database\Eloquent\Model;

class ShipmentArrival extends Model
{
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'expected'];
    protected $casts = ['hawbs' => 'array', 'scheduled_arrival' => 'datetime', 'actual_arrival' => 'datetime'];

    public const STATUSES = ['expected','in_transit','arrived','manifest_verification','partially_received','fully_received','discrepancy_detected','on_hold','collection_pending','collected','transferred_to_warehouse','closed'];
    public const TRANSITIONS = [
        'expected' => ['in_transit','arrived','on_hold','closed'],
        'in_transit' => ['arrived','on_hold'],
        'arrived' => ['manifest_verification','partially_received','fully_received','discrepancy_detected','on_hold'],
        'manifest_verification' => ['partially_received','fully_received','discrepancy_detected'],
        'partially_received' => ['fully_received','discrepancy_detected','collection_pending'],
        'fully_received' => ['collection_pending'],
        'discrepancy_detected' => ['partially_received','fully_received','on_hold','collection_pending'],
        'collection_pending' => ['collected'],
        'collected' => ['transferred_to_warehouse'],
        'transferred_to_warehouse' => ['closed'],
        'on_hold' => ['arrived','manifest_verification','closed'],
        'closed' => [],
    ];
    public function canTransitionTo(string $to): bool
    { return in_array($to, self::TRANSITIONS[$this->status] ?? [], true); }

    public function shipment() { return $this->belongsTo(\Modules\Shipment\Models\Shipment::class); }
    public function branch() { return $this->belongsTo(\Modules\Organization\Models\Branch::class); }
    public function events() { return $this->hasMany(ArrivalEvent::class, 'arrival_id')->latest(); }
    public function discrepancies() { return $this->hasMany(ArrivalDiscrepancy::class, 'arrival_id')->latest(); }
    public function documents() { return $this->hasMany(ImportDocument::class, 'arrival_id')->latest(); }
    public function customsCase() { return $this->hasOne(CustomsCase::class, 'arrival_id'); }
    public function charges() { return $this->hasMany(CustomsCharge::class, 'arrival_id'); }
    public function collections() { return $this->hasMany(AirportCollection::class, 'arrival_id'); }
    public function receipts() { return $this->hasMany(PackageReceipt::class, 'arrival_id'); }
    public function scopeFor($q, $u) { return $u->can('arrival.all_branches') || ! $u->currentAssignment?->branch_id ? $q : $q->where('branch_id', $u->currentAssignment->branch_id); }
}
