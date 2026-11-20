<?php

namespace Modules\Cargo\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Cargo\Models\CargoBox;
use Modules\Cargo\Models\CargoBoxLine;
use Modules\Cargo\Models\CargoPackage;
use Modules\Cargo\Models\CargoTrip;

class ConsolidationService
{
    /** Tolerance between box actual weight and sum of lines (KG). */
    public const WEIGHT_TOLERANCE = 0.5;

    public function __construct(private AuditService $audit) {}

    public function createBox(int $branchId, array $data, int $userId): CargoBox
    {
        return CargoBox::create([
            'reference' => $this->nextRef('BX'),
            'branch_id' => $branchId,
            'location_id' => $data['location_id'] ?? null,
            'box_type' => $data['box_type'] ?? 'carton',
            'target_weight' => $data['target_weight'] ?? 23,
            'status' => 'open',
            'packed_by' => $userId,
        ]);
    }

    /** Add a verified package to an open box. */
    public function addPackage(CargoBox $box, CargoPackage $package, ?float $weight, int $userId): CargoBoxLine
    {
        abort_unless($box->status === 'open', 422, 'Only open boxes accept packages.');
        abort_if($package->unidentified, 422, 'Unmatched packages cannot be consolidated — match a customer first.');
        abort_if(CargoBoxLine::where('package_id', $package->id)
            ->whereHas('box', fn ($q) => $q->whereNotIn('status', ['closed_out', 'opened']))->exists(),
            422, 'Package is already in an active box.');

        $line = CargoBoxLine::create([
            'box_id' => $box->id, 'package_id' => $package->id,
            'weight' => $weight ?? $package->gross_weight, 'added_at' => now(),
        ]);
        $this->audit->log('cargo.box_line_added', $box,
            new: ['package' => $package->reference ?? $package->id]);
        return $line;
    }

    /** Close + seal: weight must reconcile within tolerance. */
    public function close(CargoBox $box, float $actualWeight, string $sealNumber, int $userId): CargoBox
    {
        abort_unless($box->status === 'open', 422, 'Box is not open.');
        abort_if($box->lines()->count() === 0, 422, 'Cannot close an empty box.');
        abort_if(trim($sealNumber) === '', 422, 'Seal number is required.');

        $linesWeight = (float) $box->lines()->sum('weight');
        if (abs($linesWeight - $actualWeight) > self::WEIGHT_TOLERANCE) {
            throw ValidationException::withMessages(['actual_weight' => sprintf(
                'Weight variance %.2f KG exceeds tolerance %.2f KG (lines %.2f).',
                abs($linesWeight - $actualWeight), self::WEIGHT_TOLERANCE, $linesWeight)]);
        }
        $box->update([
            'actual_weight' => $actualWeight, 'seal_number' => $sealNumber,
            'status' => 'closed', 'closed_at' => now(),
        ]);
        $this->audit->log('cargo.box_closed', $box,
            new: ['weight' => $actualWeight, 'seal' => $sealNumber, 'lines' => $linesWeight]);
        return $box;
    }

    /** Assign a closed box to a trip; warns if passenger allowance exceeded. */
    public function assignToTrip(CargoBox $box, CargoTrip $trip, int $userId): CargoBox
    {
        abort_unless($box->status === 'closed', 422, 'Only closed, sealed and weighed boxes can be assigned.');
        abort_unless(in_array($trip->status, ['planned', 'assigned'], true), 422, 'Trip is not accepting boxes.');

        $box->update(['trip_id' => $trip->id, 'status' => 'assigned']);
        if ($trip->status === 'planned') $trip->update(['status' => 'assigned']);

        $total = (float) $trip->boxes()->sum('actual_weight');
        if ($trip->baggage_allowance && $total > $trip->baggage_allowance) {
            $this->audit->log('cargo.trip_allowance_exceeded', $trip,
                new: ['total_kg' => $total, 'allowance' => $trip->baggage_allowance]);
        }
        return $box;
    }

    private function nextRef(string $prefix): string
    {
        $p = "{$prefix}-DXB-".now()->format('Y').'-';
        $max = CargoBox::where('reference', 'like', "{$p}%")->lockForUpdate()->max('reference');
        return sprintf('%s%05d', $p, $max ? ((int) substr($max, -5)) + 1 : 1);
    }
}
