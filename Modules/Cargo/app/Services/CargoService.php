<?php
namespace Modules\Cargo\Services;

use Illuminate\Support\Facades\DB;
use Modules\Cargo\Models\{PreAlert, Receiving, CargoPackage, CargoEvent, Measurement, Inspection, Repackaging, Warehouse, WarehouseLocation, WarehouseMovement, CargoIncident};

class CargoService
{
    // ── Sequences ─────────────────────────────────────────
    public function ref(string $table, string $col, string $prefix): string
    {
        $year = now()->format('Y');
        $full = "{$prefix}-{$year}-";
        $max = DB::table($table)->where($col, 'like', "{$full}%")->orderByDesc($col)->lockForUpdate()->value($col);
        return sprintf('%s%06d', $full, $max ? ((int) substr($max, -6)) + 1 : 1);
    }
    public function packageRef(string $branchCode): string
    {
        $year = now()->format('Y');
        $full = "XER-" . strtoupper($branchCode) . "-PKG-{$year}-";
        $max = DB::table('cargo_packages')->where('reference', 'like', "{$full}%")->orderByDesc('reference')->lockForUpdate()->value('reference');
        return sprintf('%s%06d', $full, $max ? ((int) substr($max, -6)) + 1 : 1);
    }

    // ── Events / incidents ────────────────────────────────
    public function log(CargoPackage $pkg, string $event, ?int $userId, array $meta = []): void
    {
        CargoEvent::create(['package_id' => $pkg->id, 'event' => $event, 'user_id' => $userId, 'branch_id' => $pkg->branch_id, 'meta' => $meta ?: null]);
    }
    public function logReceiving(Receiving $r, string $event, ?int $userId, array $meta = []): void
    {
        CargoEvent::create(['receiving_id' => $r->id, 'event' => $event, 'user_id' => $userId, 'branch_id' => $r->branch_id, 'meta' => $meta ?: null]);
    }
    public function incident(array $data, int $branchId, ?int $userId): CargoIncident
    {
        $i = new CargoIncident($data + ['branch_id' => $branchId, 'reported_by' => $userId]);
        $i->forceFill(['reference' => $this->ref('cargo_incidents', 'reference', 'XER-INC')])->save();
        return $i;
    }

    // ── Pre-alert ─────────────────────────────────────────
    public function createPreAlert(array $data, int $userId): PreAlert
    {
        return DB::transaction(function () use ($data, $userId) {
            $a = new PreAlert($data + ['created_by' => $userId]);
            $a->forceFill(['reference' => $this->ref('cargo_pre_alerts', 'reference', 'XER-PA')])->save();
            return $a;
        });
    }

    // ── Receiving ─────────────────────────────────────────
    /** Start a physical receiving session — only on real arrival. */
    public function startReceiving(array $data, int $userId): Receiving
    {
        return DB::transaction(function () use ($data, $userId) {
            $r = new Receiving($data + [
                'received_by' => $userId, 'received_at' => now(),
                'unidentified' => $data['unidentified'] ?? empty($data['customer_id']),
            ]);
            $r->forceFill(['reference' => $this->ref('cargo_receivings', 'reference', 'XER-RCV')])->save();
            $this->logReceiving($r, 'receiving_started', $userId);
            return $r;
        });
    }

    /** Register physical packages under a receiving session. */
    public function registerPackages(Receiving $r, int $count, array $defaults, int $userId): array
    {
        return DB::transaction(function () use ($r, $count, $defaults, $userId) {
            $pkgs = [];
            $branchCode = $r->branch?->code ?? 'GEN';
            for ($i = 1; $i <= $count; $i++) {
                $p = new CargoPackage($defaults + [
                    'receiving_id' => $r->id, 'customer_id' => $r->customer_id,
                    'handover_id' => $r->handover_id, 'branch_id' => $r->branch_id,
                    'sequence' => $r->actual_packages + $i,
                    'unidentified' => $r->unidentified,
                    'status' => $r->unidentified ? 'received' : 'identified',
                ]);
                $p->forceFill(['reference' => $this->packageRef($branchCode)])->save();
                $this->log($p, 'received', $userId, ['receiving' => $r->reference]);
                $pkgs[] = $p;
            }
            $r->increment('actual_packages', $count);
            return $pkgs;
        });
    }

    /** Confirm a receiving — computes outcome vs expectation; updates pre-alert. */
    public function confirmReceiving(Receiving $r, int $userId): Receiving
    {
        return DB::transaction(function () use ($r, $userId) {
            $expected = $r->expected_packages ?: $r->preAlert?->expected_packages ?: 0;
            $outcome = $r->unidentified ? 'unidentified'
                : ($expected === 0 ? 'fully'
                : ($r->actual_packages > $expected ? 'excess'
                : ($r->actual_packages < $expected ? 'short' : 'fully')));
            $r->update(['outcome' => $outcome, 'status' => 'confirmed']);
            if ($r->preAlert) {
                $total = $r->preAlert->receivings()->sum('actual_packages');
                $r->preAlert->update(['status' => $expected && $total < $expected ? 'partially_received' : 'fully_received']);
            }
            $this->logReceiving($r, 'confirmed', $userId, ['outcome' => $outcome, 'expected' => $expected, 'actual' => $r->actual_packages]);
            if (in_array($outcome, ['short', 'excess', 'unidentified', 'damaged'])) {
                $this->incident(['package_id' => null, 'receiving_id' => $r->id, 'category' => $outcome === 'unidentified' ? 'unidentified' : $outcome . '_received',
                    'severity' => $outcome === 'unidentified' ? 'high' : 'medium',
                    'description' => "Receiving {$r->reference}: {$outcome} (expected {$expected}, got {$r->actual_packages})"], $r->branch_id, $userId);
            }
            return $r->fresh();
        });
    }

    /** Link unidentified cargo to a verified customer — never guess. */
    public function identifyPackage(CargoPackage $p, int $customerId, int $userId): CargoPackage
    {
        $p->update(['customer_id' => $customerId, 'unidentified' => false, 'status' => 'identified']);
        $this->log($p, 'linked', $userId, ['customer_id' => $customerId]);
        return $p;
    }

    // ── Weighing / measurement (history preserved) ────────
    public function measure(CargoPackage $p, array $data, int $userId, string $kind = 'initial'): Measurement
    {
        return DB::transaction(function () use ($p, $data, $userId, $kind) {
            abort_if($kind === 'reweigh' && $p->measurements()->where('kind', 'initial')->doesntExist(), 422, 'No initial measurement to reweigh.');
            $divisor = $data['divisor'] ?? 6000;
            $vol = ($data['length'] ?? null) && ($data['width'] ?? null) && ($data['height'] ?? null)
                ? round(($data['length'] * $data['width'] * $data['height']) / $divisor, 2) : null;
            $chargeable = round(max($data['gross_weight'] ?? 0, $vol ?? 0), 2);
            $m = $p->measurements()->create($data + [
                'kind' => $kind, 'volumetric_weight' => $vol, 'chargeable_weight' => $chargeable,
                'divisor' => $divisor, 'measured_by' => $userId,
            ]);
            $p->update([
                'gross_weight' => $data['gross_weight'] ?? $p->gross_weight,
                'length' => $data['length'] ?? $p->length, 'width' => $data['width'] ?? $p->width, 'height' => $data['height'] ?? $p->height,
                'volumetric_weight' => $vol ?? $p->volumetric_weight, 'chargeable_weight' => $chargeable ?: $p->chargeable_weight,
                'volumetric_divisor' => $divisor, 'status' => 'weighed',
            ]);
            $this->log($p, $kind === 'reweigh' ? 'reweighed' : 'weighed', $userId, ['chargeable' => $chargeable, 'divisor' => $divisor]);
            return $m;
        });
    }

    // ── Inspection ────────────────────────────────────────
    public function inspect(CargoPackage $p, array $data, int $userId): Inspection
    {
        return DB::transaction(function () use ($p, $data, $userId) {
            $i = $p->inspections()->create($data + ['inspected_by' => $userId]);
            $map = ['passed' => 'inspection_passed', 'passed_conditions' => 'inspection_passed',
                'repack_required' => 'on_hold', 'compliance_review' => 'on_hold', 'on_hold' => 'on_hold',
                'rejected' => 'on_hold', 'info_required' => 'on_hold'];
            $p->update(['inspection_status' => $i->outcome, 'status' => $map[$i->outcome] ?? $p->status]);
            $this->log($p, 'inspected', $userId, ['outcome' => $i->outcome]);
            if (in_array($i->outcome, ['repack_required', 'compliance_review', 'rejected'])) {
                $this->incident(['package_id' => $p->id, 'category' => $i->outcome === 'compliance_review' ? 'restricted' : 'packaging_failure',
                    'severity' => $i->outcome === 'rejected' ? 'high' : 'medium', 'description' => $i->comments], $p->branch_id, $userId);
            }
            return $i;
        });
    }

    public function releaseHold(CargoPackage $p, int $userId): CargoPackage
    {
        abort_unless($p->onHold(), 422, 'Package is not on hold.');
        $p->update(['status' => $p->inspection_status === 'passed' || $p->inspection_status === 'passed_conditions' ? 'inspection_passed' : 'received']);
        $this->log($p, 'released', $userId);
        return $p;
    }

    // ── Repackaging (original evidence preserved) ─────────
    public function repack(CargoPackage $p, array $data, int $userId): Repackaging
    {
        return DB::transaction(function () use ($p, $data, $userId) {
            $rp = $p->repackagings()->create($data + [
                'repacked_by' => $userId,
                'before_snapshot' => $p->only(['gross_weight','length','width','height','chargeable_weight','package_type']),
            ]);
            $p->update([
                'package_type' => $data['packaging_type'] ?? $p->package_type,
                'gross_weight' => $data['new_gross_weight'] ?? $p->gross_weight,
                'length' => $data['new_length'] ?? $p->length, 'width' => $data['new_width'] ?? $p->width, 'height' => $data['new_height'] ?? $p->height,
                'packaging_status' => 'repacked',
            ]);
            $this->log($p, 'repacked', $userId, ['reason' => $data['reason'] ?? null]);
            return $rp;
        });
    }

    // ── Warehouse: storage + movements (custody auditable) ─
    public function store(CargoPackage $p, WarehouseLocation $loc, int $userId): WarehouseMovement
    {
        return $this->move($p, $loc, $userId, 'inspection_to_storage', ['status' => 'stored']);
    }

    public function move(CargoPackage $p, WarehouseLocation $loc, int $userId, string $reason = 'transfer', array $pkgUpdate = []): WarehouseMovement
    {
        return DB::transaction(function () use ($p, $loc, $userId, $reason, $pkgUpdate) {
            abort_if($loc->status !== 'active', 422, 'Location inactive.');
            if ($loc->capacity && $loc->packages()->count() >= $loc->capacity && $p->location_id !== $loc->id) {
                abort(422, "Location {$loc->code} is at capacity.");
            }
            $m = new WarehouseMovement(['package_id' => $p->id, 'from_location_id' => $p->location_id, 'to_location_id' => $loc->id, 'reason' => $reason, 'moved_by' => $userId]);
            $m->forceFill(['reference' => $this->ref('warehouse_movements', 'reference', 'XER-MOV')])->save();
            $p->update(['location_id' => $loc->id] + $pkgUpdate);
            $this->log($p, 'moved', $userId, ['from' => $m->from?->code, 'to' => $loc->code, 'reason' => $reason]);
            return $m;
        });
    }

    /** Scan endpoint — idempotent per (package, event, minute window). */
    public function scan(CargoPackage $p, string $event, int $userId, ?string $locationRef = null): CargoEvent
    {
        $dupe = $p->events()->where('event', 'scanned')
            ->where('meta->scan_type', $event)->where('created_at', '>=', now()->subSeconds(30))->exists();
        $e = CargoEvent::create(['package_id' => $p->id, 'event' => 'scanned', 'user_id' => $userId,
            'branch_id' => $p->branch_id, 'location_ref' => $locationRef,
            'meta' => ['scan_type' => $event, 'duplicate' => $dupe]]);
        return $e;
    }

    // ── Dashboard stats ───────────────────────────────────
    public function dashboard($user): array
    {
        $pk = CargoPackage::for($user); $rv = Receiving::for($user); $pa = PreAlert::for($user);
        return [
            'expected_today' => (clone $pa)->whereDate('expected_date', today())->whereNotIn('status', ['cancelled','fully_received'])->count(),
            'received_today' => (clone $rv)->whereDate('received_at', today())->where('status', 'confirmed')->count(),
            'packages_today' => (clone $pk)->whereDate('created_at', today())->count(),
            'pending_receiving' => (clone $rv)->where('status', 'in_progress')->count(),
            'pending_weighing' => (clone $pk)->whereNull('chargeable_weight')->count(),
            'pending_inspection' => (clone $pk)->where('inspection_status', 'pending')->count(),
            'on_hold' => (clone $pk)->where('status', 'on_hold')->count(),
            'repack_required' => (clone $pk)->where('inspection_status', 'repack_required')->count(),
            'in_storage' => (clone $pk)->where('status', 'stored')->count(),
            'ready' => (clone $pk)->where('status', 'ready')->count(),
            'unidentified' => (clone $pk)->where('unidentified', true)->count(),
            'open_incidents' => CargoIncident::for($user)->whereIn('status', ['open','investigating'])->count(),
            'by_status' => (clone $pk)->selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status')->all(),
        ];
    }
}
