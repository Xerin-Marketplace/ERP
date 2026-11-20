<?php

namespace Modules\Cargo\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Cargo\Models\CargoTrip;

class TripService
{
    public function __construct(private AuditService $audit) {}

    public function create(array $data, int $userId): CargoTrip
    {
        return CargoTrip::create($data + [
            'reference' => $this->nextRef(), 'status' => 'planned',
        ]);
    }

    /** Dubai-side signed handover to the passenger. */
    public function handoverDubai(CargoTrip $trip, int $userId): CargoTrip
    {
        abort_unless($trip->status === 'assigned', 422, 'Assign boxes before handover.');
        abort_if($trip->boxes()->where('status', '!=', 'assigned')->exists(),
            422, 'All boxes must be assigned.');
        $trip->update([
            'status' => 'handed_over', 'handover_dxb_by' => $userId, 'handover_dxb_at' => now(),
        ]);
        $trip->boxes()->update(['status' => 'handed_over']);
        $this->audit->log('cargo.trip_handover_dxb', $trip, new: ['boxes' => $trip->boxes()->count()]);
        return $trip;
    }

    /** Departure requires handover + evidence (boarding pass / airline confirmation). */
    public function depart(CargoTrip $trip, ?int $evidenceFileId, int $userId): CargoTrip
    {
        abort_unless($trip->status === 'handed_over', 422, 'Complete Dubai handover first.');
        abort_if(! $evidenceFileId, 422, 'Departure evidence (boarding pass or airline confirmation) is required.');
        $trip->update([
            'status' => 'departed', 'departed_at' => now(),
            'departure_evidence_file_id' => $evidenceFileId,
        ]);
        $trip->boxes()->update(['status' => 'departed']);
        $this->audit->log('cargo.trip_departed', $trip);
        return $trip;
    }

    public function arrive(CargoTrip $trip): CargoTrip
    {
        abort_unless($trip->status === 'departed', 422, 'Trip is not in transit.');
        $trip->update(['status' => 'arrived', 'arrived_at' => now()]);
        $trip->boxes()->update(['status' => 'arrived']);
        $this->audit->log('cargo.trip_arrived', $trip);
        return $trip;
    }

    /** Tanzania-side signed handover from the passenger. */
    public function handoverTanzania(CargoTrip $trip, int $userId): CargoTrip
    {
        abort_unless($trip->status === 'arrived', 422, 'Trip has not arrived.');
        $trip->update([
            'status' => 'reconciled', 'handover_tz_by' => $userId, 'handover_tz_at' => now(),
        ]);
        $trip->boxes()->update(['status' => 'opened']);
        $this->audit->log('cargo.trip_handover_tz', $trip);
        return $trip;
    }

    public function weightCheck(CargoTrip $trip): array
    {
        $total = (float) $trip->boxes()->sum('actual_weight');
        return ['total_kg' => $total, 'allowance' => $trip->baggage_allowance,
            'over' => $trip->baggage_allowance ? $total > $trip->baggage_allowance : false];
    }

    private function nextRef(): string
    {
        $p = 'TR-'.now()->format('Y').'-';
        $max = CargoTrip::where('reference', 'like', "{$p}%")->lockForUpdate()->max('reference');
        return sprintf('%s%04d', $p, $max ? ((int) substr($max, -4)) + 1 : 1);
    }
}
