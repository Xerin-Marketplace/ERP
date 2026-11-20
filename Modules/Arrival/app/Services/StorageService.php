<?php

namespace Modules\Arrival\Services;

use Carbon\Carbon;
use Modules\Arrival\Models\StorageRateSetting;
use Modules\Cargo\Models\CargoPackage;

/** Storage charges — one effective-dated rule: N free days then rate/KG/day. */
class StorageService
{
    public function rateFor(?int $branchId, ?Carbon $at = null): ?StorageRateSetting
    {
        $at ??= now();
        return StorageRateSetting::where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))
            ->where('effective_from', '<=', $at->toDateString())
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $at->toDateString()))
            ->orderByRaw('branch_id IS NULL') // branch-specific wins
            ->latest('effective_from')->first();
    }

    /** Charge for a package stored from $receivedAt until now/$until. */
    public function charge(CargoPackage $package, Carbon $receivedAt, ?Carbon $until = null): array
    {
        $until ??= now();
        $rate = $this->rateFor($package->branch_id, $until);
        if (! $rate) return ['charge' => 0, 'days' => 0, 'chargeable_days' => 0, 'rate' => null];

        $days = max(0, (int) $receivedAt->startOfDay()->diffInDays($until->startOfDay()));
        $chargeable = max(0, $days - $rate->free_days);
        $kg = (float) ($package->gross_weight ?: 0);
        $charge = round($chargeable * $kg * (float) $rate->rate_per_kg_day, 2);

        return [
            'days' => $days, 'chargeable_days' => $chargeable,
            'kg' => $kg, 'rate_per_kg_day' => (float) $rate->rate_per_kg_day,
            'free_days' => $rate->free_days, 'currency' => $rate->currency, 'charge' => $charge,
        ];
    }
}
