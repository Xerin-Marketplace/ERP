<?php
namespace Modules\Shipment\Services;

use Illuminate\Support\Facades\DB;
use Modules\Cargo\Models\CargoPackage;
use Modules\Shipment\Models\{Shipment, ShipmentPackage, ShipmentEvent, AirlineBooking, AirWaybill, CargoManifest, AirportDispatch, AirlineHandover, Flight, FlightEvent, ShipmentException};

class ShipmentService
{
    public function ref(string $table, string $col, string $prefix): string
    {
        $year = now()->format('Y');
        $full = "{$prefix}-{$year}-";
        $max = DB::table($table)->where($col, 'like', "{$full}%")->orderByDesc($col)->lockForUpdate()->value($col);
        return sprintf('%s%06d', $full, $max ? ((int) substr($max, -6)) + 1 : 1);
    }

    public function log(Shipment $s, string $event, ?int $userId, array $meta = []): void
    {
        ShipmentEvent::create(['shipment_id' => $s->id, 'event' => $event, 'user_id' => $userId, 'meta' => $meta ?: null]);
    }

    public function exception(Shipment $s, string $category, string $desc, ?int $userId, array $pkgIds = []): ShipmentException
    {
        $e = new ShipmentException(['shipment_id' => $s->id, 'category' => $category, 'description' => $desc,
            'package_ids' => $pkgIds ?: null, 'reported_by' => $userId]);
        $e->forceFill(['reference' => $this->ref('shipment_exceptions', 'reference', 'XER-EXC')])->save();
        return $e;
    }

    // ── Shipment ────────────────────────────────────────
    public function create(array $data, int $userId): Shipment
    {
        return DB::transaction(function () use ($data, $userId) {
            $s = new Shipment($data + ['officer_id' => $userId]);
            $s->forceFill(['reference' => $this->ref('shipments', 'reference', 'XER-SHP')])->save();
            $this->log($s, 'created', $userId);
            return $s;
        });
    }

    public function transition(Shipment $s, string $to, int $userId, array $meta = []): Shipment
    {
        abort_unless($s->canTransitionTo($to), 422, "Cannot move shipment {$s->status} → {$to}");
        $s->update(['status' => $to] + match ($to) {
            'departed' => ['actual_departure' => now()],
            'arrived' => ['actual_arrival' => now()],
            default => [],
        });
        $this->log($s, "status:{$to}", $userId, $meta);
        return $s;
    }

    // ── Allocation (transactional, no dup, eligibility enforced) ──
    public const ELIGIBLE = ['stored', 'ready', 'inspection_passed', 'weighed'];

    public function allocate(Shipment $s, array $packageIds, int $userId): int
    {
        return DB::transaction(function () use ($s, $packageIds, $userId) {
            abort_if($s->status === 'cancelled' || $s->status === 'closed', 422, 'Shipment closed.');
            $allocated = 0;
            foreach (array_unique($packageIds) as $pid) {
                $p = CargoPackage::lockForUpdate()->findOrFail($pid);
                abort_unless($p->branch_id === $s->origin_branch_id, 403, 'Cross-branch allocation denied.');
                abort_if($p->onHold() || ! in_array($p->status, self::ELIGIBLE, true), 422,
                    "Package {$p->reference} not eligible ({$p->status}).");
                // no double allocation across active shipments
                $active = ShipmentPackage::where('package_id', $p->id)->whereNull('removed_at')
                    ->whereHas('shipment', fn ($q) => $q->whereNotIn('status', ['cancelled','closed','arrived']))->exists();
                abort_if($active, 422, "Package {$p->reference} already allocated to an active shipment.");
                ShipmentPackage::create(['shipment_id' => $s->id, 'package_id' => $p->id,
                    'allocated_by' => $userId, 'allocated_at' => now()]);
                $p->update(['status' => 'allocated']);
                $this->log($s, 'allocated', $userId, ['package' => $p->reference]);
                $allocated++;
            }
            $s->fresh()->recalcTotals();
            return $allocated;
        });
    }

    public function removePackage(Shipment $s, int $packageId, int $userId, string $reason): void
    {
        DB::transaction(function () use ($s, $packageId, $userId, $reason) {
            $sp = ShipmentPackage::where('shipment_id', $s->id)->where('package_id', $packageId)->whereNull('removed_at')->firstOrFail();
            $sp->update(['removed_at' => now(), 'removed_by' => $userId, 'remove_reason' => $reason]);
            $sp->package->update(['status' => 'stored']);
            $this->log($s, 'unallocated', $userId, ['package' => $sp->package->reference, 'reason' => $reason]);
            $s->fresh()->recalcTotals();
        });
    }

    /** Split: move listed packages to a new child shipment. */
    public function split(Shipment $s, array $packageIds, int $userId, string $reason): Shipment
    {
        return DB::transaction(function () use ($s, $packageIds, $userId, $reason) {
            $s->refresh();
            $child = $this->create(array_filter($s->only(['origin_branch_id','destination_branch_id','type','transport_mode','origin_airport','destination_airport','airline_id','currency']), fn ($v) => $v !== null) + ['parent_id' => $s->id, 'status' => 'planning'], $userId);
            foreach ($packageIds as $pid) {
                $this->removePackage($s, $pid, $userId, "split: {$reason}");
                $this->allocate($child, [$pid], $userId);
            }
            $this->log($s, 'split', $userId, ['child' => $child->reference, 'reason' => $reason]);
            return $child;
        });
    }

    // ── Booking ─────────────────────────────────────────
    public function book(Shipment $s, array $data, int $userId): AirlineBooking
    {
        $s->refresh();
        $b = new AirlineBooking($data + ['shipment_id' => $s->id,
            'package_count' => $s->total_packages ?? 0, 'total_weight' => $s->total_gross_weight ?? 0, 'chargeable_weight' => $s->total_chargeable_weight ?? 0]);
        $b->forceFill(['reference' => $this->ref('airline_bookings', 'reference', 'XER-BKG')])->save();
        $this->log($s, 'booking_created', $userId, ['booking' => $b->reference]);
        return $b;
    }

    /** Confirm booking — requires evidence (airline ref or manual note). */
    public function confirmBooking(AirlineBooking $b, array $data, int $userId): AirlineBooking
    {
        abort_if(empty($data['booking_ref']) && empty($data['airline_response']), 422, 'Booking confirmation requires airline reference or recorded response.');
        $b->update(['status' => 'confirmed', 'booking_ref' => $data['booking_ref'] ?? $b->booking_ref,
            'flight_no' => $data['flight_no'] ?? $b->flight_no, 'airline_response' => $data['airline_response'] ?? null,
            'confirmed_by' => $userId, 'confirmed_at' => now()]);
        $this->log($b->shipment, 'booking_confirmed', $userId, ['ref' => $b->booking_ref]);
        return $b;
    }

    // ── AWB ─────────────────────────────────────────────
    public function createAwb(Shipment $s, array $data, int $userId): AirWaybill
    {
        // IATA format: 3-digit prefix + 8-digit serial incl. mod-7 check digit (validate only when format matches)
        if (isset($data['awb_number']) && preg_match('/^(\d{3})-?(\d{7})(\d)$/', $data['awb_number'], $m)) {
            abort_unless((int) $m[2] % 7 === (int) $m[3], 422, 'Invalid AWB check digit.');
        }
        abort_if(AirWaybill::where('awb_number', $data['awb_number'])->where('status', '!=', 'cancelled')->exists(), 422, 'AWB number already in use.');
        $s->refresh();
        $awb = $s->waybills()->create($data + ['package_count' => $s->total_packages ?? 0,
            'gross_weight' => $s->total_gross_weight ?? 0, 'chargeable_weight' => $s->total_chargeable_weight ?? 0]);
        $this->log($s, 'awb_created', $userId, ['awb' => $awb->awb_number]);
        return $awb;
    }

    public function issueAwb(AirWaybill $awb, int $userId): AirWaybill
    {
        abort_unless(in_array($awb->status, ['draft','approved'], true), 422, "Cannot issue AWB in {$awb->status} state.");
        $awb->update(['status' => 'issued', 'issued_by' => $userId, 'issued_at' => now()]);
        $this->log($awb->shipment, 'awb_issued', $userId, ['awb' => $awb->awb_number]);
        return $awb;
    }

    /** Amend issued AWB — creates v(n+1), preserves issued version. */
    public function amendAwb(AirWaybill $awb, array $data, int $userId): AirWaybill
    {
        abort_unless($awb->status === 'issued', 422, 'Only issued AWBs are amended.');
        $new = $awb->replicate(['status','version','issued_at','issued_by']);
        $new->forceFill(['version' => $awb->version + 1, 'status' => 'draft'])->fill($data)->save();
        $awb->update(['status' => 'amended']);
        return $new;
    }

    // ── Manifest + reconciliation ───────────────────────
    public function manifest(Shipment $s, array $data, int $userId): CargoManifest
    {
        return DB::transaction(function () use ($s, $data, $userId) {
            $s->refresh();
            $m = new CargoManifest($data + ['shipment_id' => $s->id, 'destination' => $s->destination_airport,
                'total_pieces' => $s->total_packages ?? 0, 'total_gross_weight' => $s->total_gross_weight ?? 0,
                'total_chargeable_weight' => $s->total_chargeable_weight ?? 0]);
            $m->forceFill(['reference' => $this->ref('cargo_manifests', 'reference', 'XER-MNF')])->save();
            $this->log($s, 'manifest_created', $userId, ['ref' => $m->reference]);
            return $m;
        });
    }

    /** Reconcile expected vs actual — blocks approval on mandatory mismatch. */
    public function reconcile(CargoManifest $m, int $scannedCount, ?float $scannedWeight, int $userId): CargoManifest
    {
        $s = $m->shipment;
        $mismatch = $scannedCount !== $s->total_packages;
        $weightDiff = $scannedWeight !== null ? abs($scannedWeight - $s->total_gross_weight) : 0;
        $m->update(['reconciliation' => [
            'expected_pieces' => $s->total_packages, 'scanned_pieces' => $scannedCount,
            'expected_weight' => $s->total_gross_weight, 'scanned_weight' => $scannedWeight,
            'pieces_match' => ! $mismatch, 'weight_diff' => round($weightDiff, 2),
        ]]);
        if ($mismatch) {
            $this->exception($s, 'manifest_mismatch', "Manifest {$m->reference}: expected {$s->total_packages}, scanned {$scannedCount}", $userId);
            $m->update(['status' => 'draft']);
        } else {
            $m->update(['status' => 'reviewed']);
        }
        $this->log($s, 'manifest_reconciled', $userId, ['match' => ! $mismatch]);
        return $m->fresh();
    }

    public function approveManifest(CargoManifest $m, int $userId): CargoManifest
    {
        abort_unless($m->status === 'reviewed' && ($m->reconciliation['pieces_match'] ?? false), 422, 'Manifest must pass reconciliation before approval.');
        $m->update(['status' => 'approved', 'approved_by' => $userId]);
        return $m;
    }

    // ── Dispatch + airline handover ─────────────────────
    public function dispatch(Shipment $s, array $data, int $userId): AirportDispatch
    {
        $d = new AirportDispatch($data + ['shipment_id' => $s->id, 'branch_id' => $s->origin_branch_id,
            'expected_packages' => $s->total_packages ?? 0]);
        $d->forceFill(['reference' => $this->ref('airport_dispatches', 'reference', 'XER-DSP')])->save();
        $this->log($s, 'dispatch_scheduled', $userId);
        return $d;
    }

    /** Record airline acceptance — partial supported, package custody updated. */
    public function handover(Shipment $s, array $acceptedPkgIds, array $rejected, int $userId, array $data = []): AirlineHandover
    {
        return DB::transaction(function () use ($s, $acceptedPkgIds, $rejected, $userId, $data) {
            $allocated = $s->activeAllocations()->pluck('package_id')->all();
            $missing = array_values(array_diff($allocated, $acceptedPkgIds, array_keys($rejected)));
            $status = count($missing) === 0 && count($rejected) === 0 ? 'complete' : 'partial';
            $h = new AirlineHandover($data + [
                'shipment_id' => $s->id, 'airline_id' => $s->airline_id, 'airport' => $s->origin_airport,
                'expected_packages' => count($allocated), 'accepted_packages' => count($acceptedPkgIds),
                'rejected_packages' => $rejected ?: null, 'missing_packages' => $missing ?: null,
                'status' => $status, 'officer_id' => $userId,
            ]);
            $h->forceFill(['reference' => $this->ref('airline_handovers', 'reference', 'XER-AHO')])->save();
            foreach ($acceptedPkgIds as $pid) {
                CargoPackage::where('id', $pid)->update(['status' => 'dispatched']);
            }
            $this->log($s, 'airline_handover', $userId, ['accepted' => count($acceptedPkgIds), 'missing' => count($missing), 'rejected' => count($rejected)]);
            if ($missing || $rejected) {
                $this->exception($s, 'handover_failed', "Partial handover {$h->reference}: " . count($missing) . " missing, " . count($rejected) . " rejected", $userId, array_merge($missing, array_keys($rejected)));
            }
            return $h;
        });
    }

    // ── Flight events ───────────────────────────────────
    public function recordFlightEvent(Flight $f, string $event, string $source, ?int $userId, array $meta = []): FlightEvent
    {
        return DB::transaction(function () use ($f, $event, $source, $userId, $meta) {
            abort_unless(in_array($source, ['airline_api','manual','airport','tracking_provider'], true), 422, 'Invalid event source.');
            $e = $f->events()->create(['event' => $event, 'source' => $source, 'meta' => $meta ?: null, 'recorded_by' => $userId]);
            $f->update(['status' => $event] + match ($event) {
                'departed' => ['actual_departure' => now()], 'landed' => ['actual_arrival' => now()], default => [],
            });
            return $e;
        });
    }

    // ── Readiness validation ────────────────────────────
    public function readiness(Shipment $s): array
    {
        $checks = [];
        $checks['packages_allocated'] = $s->total_packages > 0;
        $checks['all_eligible'] = $s->packages()->whereNotIn('cargo_packages.status', ['allocated','dispatched'])->doesntExist();
        $checks['no_holds'] = $s->packages()->where('cargo_packages.status', 'on_hold')->doesntExist();
        $checks['booking_confirmed'] = $s->bookings()->where('status', 'confirmed')->exists();
        $checks['awb_issued'] = $s->waybills()->where('status', 'issued')->exists();
        $checks['docs_approved'] = $s->documents()->where('review_status', 'pending')->doesntExist() && $s->documents()->exists();
        $checks['manifest_approved'] = $s->manifests()->where('status', 'approved')->exists();
        $checks['no_open_exceptions'] = $s->exceptions()->where('status', 'open')->doesntExist();
        $checks['weights_recorded'] = $s->total_chargeable_weight > 0;
        return $checks;
    }

    public function dashboard($user): array
    {
        $s = Shipment::for($user);
        return [
            'active' => (clone $s)->whereNotIn('status', ['arrived','closed','cancelled'])->count(),
            'awaiting_booking' => (clone $s)->whereIn('status', ['ready_for_booking','booking_pending'])->count(),
            'in_transit' => (clone $s)->whereIn('status', ['departed','in_transit','transshipment'])->count(),
            'arrived' => (clone $s)->where('status', 'arrived')->count(),
            'delayed' => (clone $s)->where('status', 'delayed')->count(),
            'handed_over' => (clone $s)->where('status', 'handed_over')->count(),
            'open_exceptions' => ShipmentException::where('status', 'open')->count(),
            'by_status' => (clone $s)->selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status')->all(),
        ];
    }
}
