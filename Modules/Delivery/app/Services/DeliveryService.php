<?php
namespace Modules\Delivery\Services;

use Illuminate\Support\Facades\DB;
use Modules\Cargo\Models\CargoPackage;
use Modules\Arrival\Services\ArrivalService;
use Modules\Delivery\Models\{DeliveryOrder, DeliveryOrderPackage, DeliveryEvent, DeliveryAssignment,
    DeliveryAttempt, ProofOfDelivery, DeliveryReturn, CustomerCollection, CodCollection,
    DriverLocation, DeliveryIncident, Driver, Vehicle, DeliveryService as Service, DeliveryZone};

class DeliveryService
{
    public function ref(string $table, string $prefix): string
    {
        $full = "{$prefix}-" . now()->format('Y') . "-";
        $max = DB::table($table)->where('reference', 'like', "{$full}%")->orderByDesc('reference')->lockForUpdate()->value('reference');
        return sprintf('%s%06d', $full, $max ? ((int) substr($max, -6)) + 1 : 1);
    }

    public function log(DeliveryOrder $o, string $event, ?int $userId = null, ?int $driverId = null, array $meta = [], string $actor = 'staff'): void
    {
        DeliveryEvent::create(['order_id' => $o->id, 'event' => $event, 'actor_type' => $actor,
            'user_id' => $userId, 'driver_id' => $driverId, 'meta' => $meta ?: null]);
    }

    // ── Pricing ──────────────────────────────────────────
    public function price(Service $svc, ?DeliveryZone $zone, float $totalWeight): array
    {
        $fee = match ($svc->pricing_method) {
            'weight' => $svc->base_fee + ($svc->per_kg_fee * $totalWeight),
            'zone_fixed', 'contract' => ($zone?->fee ?? 0) ?: $svc->base_fee,
            'hybrid' => ($zone?->fee ?? $svc->base_fee) + ($svc->per_kg_fee * $totalWeight),
            default => $svc->base_fee, // distance needs routing provider → base only, flagged
        };
        $fee = max($fee, $svc->min_fee ?? 0);
        if ($svc->max_fee) $fee = min($fee, $svc->max_fee);
        return ['fee' => round($fee, 2), 'breakdown' => [
            'method' => $svc->pricing_method, 'base' => $svc->base_fee,
            'zone_fee' => $zone?->fee, 'weight' => $totalWeight, 'per_kg' => $svc->per_kg_fee,
            'estimated' => $svc->pricing_method === 'distance',
        ]];
    }

    // ── Order creation — only release-ready packages ────
    public function create(array $data, array $packageIds, int $userId): DeliveryOrder
    {
        return DB::transaction(function () use ($data, $packageIds, $userId) {
            $arrival = app(ArrivalService::class);
            $packages = collect();
            foreach (array_unique($packageIds) as $pid) {
                $p = CargoPackage::lockForUpdate()->findOrFail($pid);
                abort_unless($arrival->releaseCheck($p)['ready'], 422, "Package {$p->reference} not release-ready.");
                $active = DeliveryOrderPackage::where('package_id', $p->id)->whereNull('removed_at')
                    ->whereHas('order', fn ($q) => $q->whereNotIn('status', ['cancelled','closed','delivered','collected_by_customer']))->exists();
                abort_if($active, 422, "Package {$p->reference} already on an active delivery.");
                $packages->push($p);
            }
            $weight = $packages->sum('chargeable_weight') ?: $packages->sum('gross_weight');
            $svc = isset($data['service_id']) ? Service::find($data['service_id']) : null;
            $zone = isset($data['zone_id']) ? DeliveryZone::find($data['zone_id']) : null;
            abort_if($svc && ! $svc->active, 422, 'Service inactive.');
            abort_if($svc?->max_package_weight && $weight > $svc->max_package_weight, 422, 'Weight exceeds service limit.');
            $pricing = $svc ? $this->price($svc, $zone, $weight) : ['fee' => null, 'breakdown' => null];

            $o = new DeliveryOrder($data + ['fee' => $pricing['fee'], 'price_breakdown' => $pricing['breakdown'], 'status' => 'pending_release']);
            $o->forceFill(['reference' => $this->ref('delivery_orders', 'XER-DLV-TZ')])->save();
            foreach ($packages as $p) {
                DeliveryOrderPackage::create(['order_id' => $o->id, 'package_id' => $p->id, 'added_at' => now()]);
                $p->update(['status' => 'out_for_delivery']);
            }
            $this->log($o, 'created', $userId, null, ['packages' => $packages->pluck('reference')->all()]);
            return $o;
        });
    }

    public function transition(DeliveryOrder $o, string $to, ?int $userId = null, ?int $driverId = null, array $meta = []): DeliveryOrder
    {
        abort_unless($o->canTransitionTo($to), 422, "Cannot move {$o->status} → {$to}");
        $o->update(['status' => $to]);
        $actor = $driverId ? 'driver' : 'staff';
        $this->log($o, "status:{$to}", $userId, $driverId, $meta, $actor);
        return $o;
    }

    // ── Driver availability ──────────────────────────────
    public function setAvailability(Driver $d, string $status, ?float $lat = null, ?float $lng = null): Driver
    {
        abort_unless(in_array($status, ['offline','available','busy','on_delivery','on_break'], true), 422);
        abort_if($status !== 'offline' && $d->verification_status !== 'verified', 422, 'Driver not verified.');
        $d->update(['availability' => $status, 'last_active_at' => now()]);
        if ($lat !== null && $lng !== null) $this->recordLocation($d, $lat, $lng);
        return $d;
    }

    public function recordLocation(Driver $d, float $lat, float $lng, ?float $acc = null, ?int $orderId = null): DriverLocation
    {
        // only while online/on delivery — privacy control
        abort_if($d->availability === 'offline', 422, 'Driver offline — location not recorded.');
        abort_if(abs($lat) > 90 || abs($lng) > 180, 422, 'Invalid coordinates.');
        // dedupe identical events within 15s
        $dupe = DriverLocation::where('driver_id', $d->id)->where('lat', $lat)->where('lng', $lng)
            ->where('recorded_at', '>=', now()->subSeconds(15))->exists();
        if ($dupe) return DriverLocation::where('driver_id', $d->id)->latest('recorded_at')->first();
        return DriverLocation::create(['driver_id' => $d->id, 'order_id' => $orderId,
            'lat' => $lat, 'lng' => $lng, 'accuracy' => $acc, 'recorded_at' => now()]);
    }

    // ── Dispatch ─────────────────────────────────────────
    public function assign(DeliveryOrder $o, Driver $d, ?Vehicle $v, int $userId): DeliveryAssignment
    {
        return DB::transaction(function () use ($o, $d, $v, $userId) {
            $o = DeliveryOrder::lockForUpdate()->find($o->id);
            abort_unless(in_array($o->status, ['unassigned','ready_for_dispatch','assignment_pending'], true), 422, "Order {$o->status} cannot be assigned.");
            abort_unless($d->isEligible(), 422, 'Driver not eligible (verification/availability/licence).');
            abort_unless($d->branch_id === $o->branch_id, 403, 'Driver is on a different branch.');
            if ($v) {
                abort_unless($v->isAvailable(), 422, 'Vehicle not available.');
                $pkgCount = $o->activePackages()->count();
                abort_if($v->capacity_packages && $pkgCount > $v->capacity_packages, 422, 'Vehicle package capacity exceeded.');
            }
            abort_if(DeliveryAssignment::where('order_id', $o->id)->whereIn('status', ['offered','accepted'])->exists(), 422, 'Order already has an active assignment.');
            $a = DeliveryAssignment::create(['order_id' => $o->id, 'driver_id' => $d->id,
                'vehicle_id' => $v?->id, 'offered_at' => now(), 'assigned_by' => $userId]);
            $o->update(['driver_id' => $d->id, 'vehicle_id' => $v?->id, 'status' => 'assigned']);
            $d->update(['availability' => 'busy']);
            $this->log($o, 'assigned', $userId, $d->id, ['driver' => $d->name]);
            return $a;
        });
    }

    public function driverRespond(DeliveryAssignment $a, bool $accept): DeliveryAssignment
    {
        return DB::transaction(function () use ($a, $accept) {
            abort_unless($a->status === 'offered', 422);
            $a->update(['status' => $accept ? 'accepted' : 'rejected', 'responded_at' => now()]);
            $o = $a->order;
            if ($accept) {
                $o->update(['status' => 'driver_accepted']);
                $a->driver->update(['availability' => 'on_delivery']);
            } else {
                $o->update(['driver_id' => null, 'vehicle_id' => null, 'status' => 'unassigned']);
                $a->driver->update(['availability' => 'available']);
            }
            $this->log($o, $accept ? 'driver_accepted' : 'driver_rejected', null, $a->driver_id, [], 'driver');
            return $a;
        });
    }

    /** Auto-assign: pick best eligible driver on the order's branch. */
    public function autoAssign(DeliveryOrder $o, int $userId): ?DeliveryAssignment
    {
        $busy = DeliveryAssignment::whereIn('status', ['offered','accepted'])->pluck('driver_id')->all();
        $driver = Driver::where('branch_id', $o->branch_id)->where('verification_status', 'verified')
            ->whereIn('availability', ['available','busy'])->whereNotIn('id', $busy)
            ->orderBy('last_active_at')->first();
        if (! $driver) {
            $o->update(['status' => 'unassigned']); // stays in visible queue
            $this->log($o, 'auto_assign_no_driver', $userId);
            return null;
        }
        return $this->assign($o, $driver, null, $userId);
    }

    // ── POD ──────────────────────────────────────────────
    public function confirmDelivery(DeliveryOrder $o, array $data, int $driverId): ProofOfDelivery
    {
        return DB::transaction(function () use ($o, $data, $driverId) {
            $o = DeliveryOrder::lockForUpdate()->find($o->id);
            abort_unless($o->status === 'arrived_at_destination' || $o->status === 'delivery_attempted' || $o->status === 'in_transit', 422, "Cannot deliver in {$o->status}.");
            abort_if(ProofOfDelivery::where('order_id', $o->id)->exists(), 422, 'POD already submitted.');
            $svc = $o->service;
            if ($svc?->requires_otp) {
                abort_if(empty($data['otp_code']), 422, 'OTP required.');
            }
            if ($svc?->requires_signature) {
                abort_if(empty($data['signature_path']) && empty($data['recipient_name']), 422, 'Recipient confirmation required.');
            }
            $scanned = $data['scanned_package_ids'] ?? [];
            $expected = $o->activePackages()->pluck('package_id')->all();
            abort_if($scanned && array_diff($scanned, $expected), 422, 'Scanned package not on this order.');
            $pod = ProofOfDelivery::create($data + ['order_id' => $o->id, 'driver_id' => $driverId, 'delivered_at' => now()]);
            $o->update(['status' => 'delivered']);
            $o->packages()->update(['status' => 'delivered']);
            $o->driver?->update(['availability' => 'available']);
            $this->log($o, 'delivered', null, $driverId, ['pod' => $pod->id], 'driver');
            return $pod;
        });
    }

    // ── Failed delivery ──────────────────────────────────
    public function recordFailure(DeliveryOrder $o, string $reason, int $driverId, array $data = []): DeliveryAttempt
    {
        abort_unless(in_array($reason, DeliveryAttempt::REASONS, true), 422);
        $att = $o->attempts()->create($data + ['driver_id' => $driverId, 'reason' => $reason]);
        if (in_array($o->status, ['in_transit','arrived_at_destination','delivery_attempted'], true)) {
            $o->update(['status' => 'failed_delivery']);
        }
        $this->log($o, 'delivery_failed', null, $driverId, ['reason' => $reason], 'driver');
        return $att;
    }

    public function reschedule(DeliveryOrder $o, string $date, int $userId): DeliveryOrder
    {
        abort_unless(in_array($o->status, ['failed_delivery','reschedule_requested'], true), 422, 'Cannot reschedule.');
        $o->update(['status' => 'rescheduled', 'scheduled_date' => $date]);
        $this->log($o, 'rescheduled', $userId, null, ['date' => $date]);
        return $o;
    }

    // ── Returns ──────────────────────────────────────────
    public function initiateReturn(DeliveryOrder $o, string $reason, int $userId): DeliveryReturn
    {
        abort_unless(in_array($o->status, ['failed_delivery','in_transit','delivered'], true), 422, 'Cannot return.');
        abort_if(DeliveryReturn::where('order_id', $o->id)->whereNotIn('status', ['closed'])->exists(), 422, 'Active return exists.');
        $r = new DeliveryReturn(['order_id' => $o->id, 'reason' => $reason,
            'package_ids' => $o->activePackages()->pluck('package_id')->all()]);
        $r->forceFill(['reference' => $this->ref('delivery_returns', 'XER-RTN')])->save();
        $o->update(['status' => 'return_initiated']);
        $this->log($o, 'return_initiated', $userId, null, ['reason' => $reason]);
        return $r;
    }

    public function receiveReturn(DeliveryReturn $r, int $userId): DeliveryReturn
    {
        return DB::transaction(function () use ($r, $userId) {
            $r->update(['status' => 'received_warehouse', 'received_at' => now()]);
            CargoPackage::whereIn('id', $r->package_ids)->update(['status' => 'received_tz']); // back to warehouse custody
            $r->order->update(['status' => 'returned_to_warehouse']);
            $this->log($r->order, 'return_received', $userId);
            return $r;
        });
    }

    // ── Customer collection ──────────────────────────────
    public function recordCollection(DeliveryOrder $o, array $data, int $userId): CustomerCollection
    {
        abort_unless($o->status === 'collection_pending', 422, 'Order not pending collection.');
        abort_unless($o->customer_id === $data['customer_id'], 422, 'Customer mismatch.');
        if ($o->service?->requires_otp) abort_if(empty($data['otp_code']), 422, 'OTP required.');
        $c = new CustomerCollection($data + ['order_id' => $o->id,
            'package_ids' => $o->activePackages()->pluck('package_id')->all(),
            'verified_by' => $userId, 'collected_at' => now()]);
        $c->forceFill(['reference' => $this->ref('customer_collections', 'XER-COL')])->save();
        $o->update(['status' => 'collected_by_customer']);
        $o->packages()->update(['status' => 'collected']);
        $this->log($o, 'collected_by_customer', $userId);
        return $c;
    }

    // ── COD ──────────────────────────────────────────────
    public function recordCod(DeliveryOrder $o, array $data, int $driverId): CodCollection
    {
        abort_if(empty($o->cod_amount) && empty($data['amount']), 422, 'No COD expected.');
        $expected = $o->cod_amount ?? $data['amount'];
        return $o->cod()->create($data + ['amount' => $data['amount'] ?? $expected,
            'collected_by' => $driverId, 'status' => 'collected']);
    }

    public function reconcileCod(CodCollection $cod, int $userId, ?string $ref): CodCollection
    {
        abort_if(empty($ref) && empty($cod->payment_ref), 422, 'Payment reference required to reconcile.');
        $cod->update(['status' => 'reconciled', 'reconciled_by' => $userId, 'reconciled_at' => now(), 'payment_ref' => $ref ?? $cod->payment_ref]);
        return $cod;
    }

    // ── Customer tracking (verified events only) ─────────
    public function track(DeliveryOrder $o): array
    {
        $public = ['created','assigned','driver_accepted','status:picked_up','status:in_transit','status:arrived_at_destination','status:delivered','status:failed_delivery','rescheduled','status:collected_by_customer','return_initiated'];
        $loc = $o->driver?->latestLocation;
        $stale = ! $loc || $loc->recorded_at < now()->subMinutes(10);
        return [
            'reference' => $o->reference, 'status' => $o->status,
            'events' => $o->events()->whereIn('event', $public)->get(['event','created_at']),
            'driver_assigned' => (bool) $o->driver_id,
            'live_location' => $stale ? null : $loc?->only(['lat','lng','recorded_at']),
            'live_available' => ! $stale,
        ];
    }

    public function dashboard($user): array
    {
        $o = DeliveryOrder::for($user);
        return [
            'pending_dispatch' => (clone $o)->whereIn('status', ['pending_release','ready_for_dispatch'])->count(),
            'unassigned' => (clone $o)->where('status', 'unassigned')->count(),
            'active' => (clone $o)->whereIn('status', ['assigned','driver_accepted','pickup_pending','picked_up','in_transit','arrived_at_destination'])->count(),
            'delivered' => (clone $o)->where('status', 'delivered')->count(),
            'failed' => (clone $o)->where('status', 'failed_delivery')->count(),
            'returns' => DeliveryReturn::whereNotIn('status', ['closed'])->count(),
            'cod_outstanding' => CodCollection::where('status', 'collected')->sum('amount'),
            'drivers_online' => Driver::whereIn('availability', ['available','busy','on_delivery'])->count(),
        ];
    }
}
