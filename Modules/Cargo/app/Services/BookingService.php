<?php

namespace Modules\Cargo\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Cargo\Models\CargoBooking;
use Modules\Cargo\Models\CargoPackage;

class BookingService
{
    public function __construct(private AuditService $audit) {}

    public function create(array $data, int $userId): CargoBooking
    {
        return DB::transaction(function () use ($data, $userId) {
            $b = CargoBooking::create($data + [
                'reference' => $this->nextRef(), 'status' => 'pending', 'created_by' => $userId,
            ]);
            $this->audit->log('cargo.booking_created', $b,
                new: ['customer_id' => $b->customer_id, 'mode' => $b->carriage_mode]);
            return $b;
        });
    }

    public function confirm(CargoBooking $booking): CargoBooking
    {
        abort_unless($booking->status === 'pending', 422, 'Only pending bookings can be confirmed.');
        $booking->update(['status' => 'confirmed']);
        $this->audit->log('cargo.booking_confirmed', $booking);
        return $booking;
    }

    /** Attach a received package to its booking; clears the unmatched flag. */
    public function matchPackage(CargoBooking $booking, CargoPackage $package, int $userId): CargoPackage
    {
        abort_unless(in_array($booking->status, ['pending', 'confirmed'], true), 422, 'Booking is closed.');
        $package->update([
            'booking_id' => $booking->id, 'customer_id' => $booking->customer_id,
            'unidentified' => false,
        ]);
        if (! $package->tracking_no) {
            $package->update(['tracking_no' => $this->trackingNo()]);
        }
        $this->audit->log('cargo.booking_matched', $booking,
            new: ['package' => $package->id, 'tracking' => $package->tracking_no]);
        return $package;
    }

    /** XRN + 9 digits — replaces the "N-A" placeholder forever. */
    public function trackingNo(): string
    {
        do {
            $no = 'XRN'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        } while (CargoPackage::where('tracking_no', $no)->exists());
        return $no;
    }

    private function nextRef(): string
    {
        $p = 'BK-'.now()->format('Y').'-';
        $max = CargoBooking::where('reference', 'like', "{$p}%")->lockForUpdate()->max('reference');
        return sprintf('%s%06d', $p, $max ? ((int) substr($max, -6)) + 1 : 1);
    }
}
