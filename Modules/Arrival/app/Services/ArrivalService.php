<?php
namespace Modules\Arrival\Services;

use Illuminate\Support\Facades\DB;
use Modules\Cargo\Models\CargoPackage;
use Modules\Shipment\Models\Shipment;
use Modules\Arrival\Models\{ShipmentArrival, ArrivalEvent, ArrivalDiscrepancy, ImportDocument,
    ClearingAgent, CustomsCase, CustomsCaseEvent, CustomsCharge, AirportCollection,
    PackageReceipt, DistributionBatch, ReleaseOverride};

class ArrivalService
{
    public function ref(string $table, string $prefix): string
    {
        $full = "{$prefix}-" . now()->format('Y') . "-";
        $max = DB::table($table)->where('reference', 'like', "{$full}%")->orderByDesc('reference')->lockForUpdate()->value('reference');
        return sprintf('%s%06d', $full, $max ? ((int) substr($max, -6)) + 1 : 1);
    }

    public function log(ShipmentArrival $a, string $event, ?int $userId, string $source = 'manual', array $meta = []): void
    {
        ArrivalEvent::create(['arrival_id' => $a->id, 'event' => $event, 'user_id' => $userId, 'source' => $source, 'meta' => $meta ?: null]);
    }

    public function discrepancy(ShipmentArrival $a, string $kind, array $data, ?int $userId): ArrivalDiscrepancy
    {
        $d = new ArrivalDiscrepancy($data + ['arrival_id' => $a->id, 'kind' => $kind, 'reported_by' => $userId]);
        $d->forceFill(['reference' => $this->ref('arrival_discrepancies', 'XER-DSC')])->save();
        return $d;
    }

    // ── Registration (no duplicate shipment arrival) ─────
    public function register(Shipment $s, int $tzBranchId, int $userId, array $data = []): ShipmentArrival
    {
        return DB::transaction(function () use ($s, $tzBranchId, $userId, $data) {
            abort_if(ShipmentArrival::where('shipment_id', $s->id)->exists(), 422, "Arrival already registered for {$s->reference}.");
            $s->refresh();
            $a = new ShipmentArrival($data + [
                'shipment_id' => $s->id, 'branch_id' => $tzBranchId,
                'airline_id' => $s->airline_id, 'origin_airport' => $s->origin_airport,
                'destination_airport' => $s->destination_airport ?? 'DAR',
                'expected_packages' => $s->total_packages, 'expected_weight' => $s->total_gross_weight,
                'mawb' => $s->waybills()->where('kind', 'mawb')->latest('version')->value('awb_number'),
                'hawbs' => $s->waybills()->where('kind', 'hawb')->pluck('awb_number')->all() ?: null,
                'flight_no' => $s->bookings()->where('status', 'confirmed')->latest()->value('flight_no'),
                'officer_id' => $userId,
            ]);
            $a->forceFill(['reference' => $this->ref('shipment_arrivals', 'XER-ARR-TZ')])->save();
            $this->log($a, 'registered', $userId, 'manual', ['shipment' => $s->reference]);
            return $a;
        });
    }

    public function transition(ShipmentArrival $a, string $to, int $userId, string $source = 'manual', array $meta = []): ShipmentArrival
    {
        abort_unless($a->canTransitionTo($to), 422, "Cannot move arrival {$a->status} → {$to}");
        $a->update(['status' => $to] + ($to === 'arrived' ? ['actual_arrival' => now()] : []));
        $this->log($a, "status:{$to}", $userId, $source, $meta);
        if ($to === 'arrived') {
            $a->shipment?->update(['actual_arrival' => $a->fresh()->actual_arrival]);
        }
        return $a;
    }

    // ── Manifest reconciliation ──────────────────────────
    /** Compare expected (Dubai allocation) vs physically scanned package IDs. */
    public function reconcile(ShipmentArrival $a, array $scannedIds, int $userId): array
    {
        return DB::transaction(function () use ($a, $scannedIds, $userId) {
            $expected = $a->shipment->activeAllocations()->pluck('package_id')->all();
            $scanned = array_unique($scannedIds);
            $missing = array_diff($expected, $scanned);
            $excess = array_diff($scanned, $expected);
            foreach ($missing as $pid) {
                $this->discrepancy($a, 'missing', ['package_id' => $pid, 'description' => 'Expected package not received'], $userId);
            }
            foreach ($excess as $pid) {
                $known = CargoPackage::find($pid);
                $this->discrepancy($a, $known ? 'excess' : 'unidentified', ['package_id' => $pid,
                    'description' => $known ? 'Package not on manifest' : 'Unidentified cargo scanned'], $userId);
            }
            // weight check
            $rec = $a->shipment->manifests()->latest()->first()?->reconciliation;
            if ($rec && ($rec['weight_diff'] ?? 0) > 0) {
                $this->discrepancy($a, 'weight_mismatch', ['expected_qty' => $rec['expected_weight'] ?? null,
                    'actual_qty' => $rec['scanned_weight'] ?? null, 'description' => 'Manifest weight mismatch carried over'], $userId);
            }
            $a->update(['received_packages' => count(array_intersect($expected, $scanned))]);
            $to = $missing || $excess ? 'discrepancy_detected' : 'fully_received';
            if (! $missing && ! $excess && count($scanned) < count($expected)) $to = 'partially_received';
            if ($missing && $a->received_packages > 0) $to = 'partially_received';
            $a->update(['status' => $to]);
            $this->log($a, 'reconciled', $userId, 'manual', ['expected' => count($expected), 'scanned' => count($scanned), 'missing' => count($missing), 'excess' => count($excess)]);
            return ['expected' => $expected, 'missing' => array_values($missing), 'excess' => array_values($excess)];
        });
    }

    // ── Customs ──────────────────────────────────────────
    public function openCase(ShipmentArrival $a, array $data, int $userId): CustomsCase
    {
        abort_if($a->customsCase()->exists(), 422, 'Customs case already exists.');
        $c = new CustomsCase($data + ['arrival_id' => $a->id]);
        $c->forceFill(['reference' => $this->ref('customs_cases', 'XER-CLR')])->save();
        $this->caseEvent($c, 'opened', $userId);
        return $c;
    }

    public function caseEvent(CustomsCase $c, string $event, ?int $userId, ?string $evidence = null, array $meta = []): void
    {
        CustomsCaseEvent::create(['case_id' => $c->id, 'event' => $event, 'evidence' => $evidence, 'user_id' => $userId, 'meta' => $meta ?: null]);
    }

    public function transitionCase(CustomsCase $c, string $to, int $userId, array $data = []): CustomsCase
    {
        abort_unless($c->canTransitionTo($to), 422, "Cannot move case {$c->status} → {$to}");
        // evidence gates — never release/pay without proof
        if ($to === 'payment_recorded') {
            abort_if(empty($data['payment_evidence']) && empty($c->payment_evidence), 422, 'Payment evidence required.');
        }
        if ($to === 'released') {
            abort_if(empty($data['release_evidence']) && empty($c->release_evidence), 422, 'Customs release evidence required.');
            abort_unless($c->status === 'awaiting_release', 422);
        }
        $c->update(['status' => $to] + array_filter($data, fn ($v) => $v !== null));
        $this->caseEvent($c->fresh(), "status:{$to}", $userId, $data['payment_evidence'] ?? $data['release_evidence'] ?? null);
        return $c->fresh();
    }

    // ── Charges (record-only; Finance posting via approval) ──
    public function recordCharge(ShipmentArrival $a, array $data, int $userId): CustomsCharge
    {
        $d = validator($data, ['category' => 'required|string', 'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3', 'customer_id' => 'nullable|exists:customers,id'])->validate();
        return $a->charges()->create($d);
    }

    // ── Airport collection (requires customs released) ───
    public function requestCollection(ShipmentArrival $a, array $data, int $userId): AirportCollection
    {
        $c = new AirportCollection($data + ['arrival_id' => $a->id, 'expected_packages' => $a->expected_packages]);
        $c->forceFill(['reference' => $this->ref('airport_collections', 'XER-APC')])->save();
        $this->log($a, 'collection_requested', $userId);
        return $c;
    }

    public function approveCollection(AirportCollection $col, int $userId, ?string $releaseRef): AirportCollection
    {
        $a = $col->arrival;
        abort_unless($a->customsCase?->status === 'released', 422, 'Customs release required before collection.');
        abort_if(empty($releaseRef) && empty($col->airport_release_ref), 422, 'Airport release reference required.');
        $col->update(['status' => 'approved', 'approved_at' => now(), 'airport_release_ref' => $releaseRef ?? $col->airport_release_ref]);
        return $col;
    }

    public function confirmCollection(AirportCollection $col, array $scannedIds, int $userId): AirportCollection
    {
        return DB::transaction(function () use ($col, $scannedIds, $userId) {
            abort_unless($col->status === 'approved', 422, 'Collection must be approved first.');
            $expected = $col->arrival->shipment->activeAllocations()->pluck('package_id')->all();
            $missing = array_diff($expected, $scannedIds);
            foreach ($missing as $pid) {
                $this->discrepancy($col->arrival, 'missing', ['package_id' => $pid, 'description' => 'Not collected at airport'], $userId);
            }
            $col->update(['status' => 'collected', 'collected_at' => now(),
                'collected_packages' => count($scannedIds), 'scanned_package_ids' => array_values($scannedIds)]);
            if ($col->arrival->canTransitionTo('collected')) {
                $this->transition($col->arrival, 'collected', $userId);
            }
            $this->log($col->arrival, 'airport_collected', $userId, 'manual', ['collected' => count($scannedIds), 'missing' => count($missing)]);
            return $col->fresh();
        });
    }

    // ── TZ warehouse receiving (unique per package — no dup) ──
    public function receivePackage(ShipmentArrival $a, int $packageId, int $userId, array $data = []): PackageReceipt
    {
        return DB::transaction(function () use ($a, $packageId, $userId, $data) {
            abort_if(PackageReceipt::where('package_id', $packageId)->exists(), 422, 'Package already physically received.');
            $p = CargoPackage::lockForUpdate()->findOrFail($packageId);
            $r = PackageReceipt::create($data + ['package_id' => $p->id, 'arrival_id' => $a->id,
                'received_by' => $userId, 'received_at' => now()]);
            $p->update(['status' => 'received_tz', 'branch_id' => $a->branch_id]);
            if (($data['condition'] ?? 'good') !== 'good') {
                $this->discrepancy($a, 'damaged', ['package_id' => $p->id, 'description' => 'Received damaged at TZ warehouse'], $userId);
            }
            $a->increment('received_packages');
            $this->log($a, 'package_received', $userId, 'manual', ['package' => $p->reference, 'condition' => $data['condition'] ?? 'good']);
            return $r;
        });
    }

    // ── Release readiness ────────────────────────────────
    public function releaseCheck(CargoPackage $p): array
    {
        $shipmentId = \Modules\Shipment\Models\ShipmentPackage::where('package_id', $p->id)->whereNull('removed_at')->latest()->value('shipment_id');
        $a = $shipmentId ? ShipmentArrival::where('shipment_id', $shipmentId)->first() : null;
        $receipt = PackageReceipt::where('package_id', $p->id)->exists();
        $case = $a?->customsCase;
        $chargesPending = $a ? $a->charges()->where('payment_status', 'unpaid')->where('approval_status', 'approved')->exists() : false;
        $hold = $p->onHold();
        $overrides = ReleaseOverride::where('package_id', $p->id)->pluck('check')->all();
        $checks = [
            'physically_received' => $receipt || in_array('physically_received', $overrides),
            'customs_released' => ($case?->status === 'released') || in_array('customs_released', $overrides),
            'charges_settled' => ! $chargesPending || in_array('charges_settled', $overrides),
            'not_on_hold' => ! $hold,
            'no_open_discrepancy' => ! $a || $a->discrepancies()->where('package_id', $p->id)->where('status', 'open')->doesntExist(),
        ];
        $checks['ready'] = ! in_array(false, $checks, true);
        return $checks;
    }

    public function overrideRelease(CargoPackage $p, string $check, string $reason, int $userId): ReleaseOverride
    {
        return ReleaseOverride::create(['package_id' => $p->id, 'check' => $check, 'reason' => $reason, 'user_id' => $userId]);
    }

    // ── Distribution ─────────────────────────────────────
    public function createBatch(array $packageIds, array $data, int $userId): DistributionBatch
    {
        return DB::transaction(function () use ($packageIds, $data, $userId) {
            $b = new DistributionBatch($data);
            $b->forceFill(['reference' => $this->ref('distribution_batches', 'XER-DBT')])->save();
            foreach ($packageIds as $pid) {
                $chk = $this->releaseCheck(CargoPackage::findOrFail($pid));
                abort_unless($chk['ready'], 422, "Package #{$pid} not release-ready.");
                $b->items()->create(['package_id' => $pid]);
            }
            return $b;
        });
    }

    /** Phase 6 handover payload — only eligible packages. */
    public function handoverPayload(DistributionBatch $b): array
    {
        $pkgs = $b->packages()->with('customer')->get();
        return [
            'batch_reference' => $b->reference,
            'customer' => $b->customer?->only(['id', 'code', 'name', 'phone', 'email']),
            'zone' => $b->zone,
            'packages' => $pkgs->map(fn ($p) => [
                'id' => $p->id, 'reference' => $p->reference, 'status' => $p->status,
                'customer_id' => $p->customer_id, 'weight' => $p->gross_weight,
                'release' => $this->releaseCheck($p),
            ])->all(),
            'handed_over_at' => $b->handed_over_at,
        ];
    }

    public function dashboard($user): array
    {
        $a = ShipmentArrival::for($user);
        return [
            'expected' => (clone $a)->where('status', 'expected')->count(),
            'in_transit' => (clone $a)->where('status', 'in_transit')->count(),
            'arrived' => (clone $a)->where('status', 'arrived')->count(),
            'reconciling' => (clone $a)->whereIn('status', ['manifest_verification','partially_received','discrepancy_detected'])->count(),
            'customs_pending' => CustomsCase::whereNotIn('status', ['released','cancelled'])->count(),
            'awaiting_collection' => AirportCollection::whereIn('status', ['requested','approved'])->count(),
            'open_discrepancies' => ArrivalDiscrepancy::where('status', 'open')->count(),
            'received' => (clone $a)->whereIn('status', ['fully_received','collected','transferred_to_warehouse','closed'])->count(),
        ];
    }
}
