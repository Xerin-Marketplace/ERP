<?php

namespace Modules\SystemAdministration\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Modules\SystemAdministration\Models\FeatureFlag;

/**
 * Backend feature-flag evaluation. Scope narrows who sees the feature;
 * enabled + in-window is required first. Empty scope = everyone.
 */
class FeatureFlagService
{
    public function enabled(string $key, ?User $user = null): bool
    {
        $flag = Cache::remember("ff:{$key}", 60,
            fn () => FeatureFlag::where('key', $key)->first()?->only(['key', 'is_enabled', 'scope', 'starts_at', 'ends_at']));
        $flag = $flag ? (object) $flag : null;
        if (! $flag || ! $flag->is_enabled) {
            return false;
        }
        if (($flag->starts_at && strtotime($flag->starts_at) > time()) || ($flag->ends_at && strtotime($flag->ends_at) < time())) {
            return false;
        }

        $scope = $flag->scope ?? [];
        if (empty($scope)) {
            return true;
        }

        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        if (! empty($scope['roles']) && $user->hasAnyRole($scope['roles'])) {
            return true;
        }
        if (! empty($scope['users']) && in_array($user->id, $scope['users'])) {
            return true;
        }
        if (! empty($scope['companies']) && in_array($user->profile?->company_id ?? $user->branch?->company_id, $scope['companies'])) {
            return true;
        }

        return false;
    }

    public function flush(): void
    {
        // Cache is keyed per flag; cheap approach — flush the prefix by forgetting known keys.
        FeatureFlag::pluck('key')->each(fn ($k) => Cache::forget("ff:{$k}"));
    }
}
