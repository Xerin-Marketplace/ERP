<?php

namespace Modules\Reception\Services;

use App\Enums\AccountStatus;
use App\Services\AuditService;
use Carbon\Carbon;
use Modules\Reception\Models\ExternalUserProfile;

/**
 * Enforce time-boxed external access: external accounts whose
 * expires_on has passed are suspended automatically (spec — external
 * access is temporary and must never linger).
 */
class AccessExpirationService
{
    public function __construct(private AuditService $audit) {}

    /** @return int number of accounts suspended */
    public function suspendExpired(?Carbon $today = null): int
    {
        $today = ($today ?? today())->toDateString();

        $profiles = ExternalUserProfile::with('user')
            ->whereDate('expires_on', '<=', $today)
            ->whereHas('user', fn ($q) => $q->where('status', '!=', AccountStatus::Suspended->value))
            ->get();

        foreach ($profiles as $profile) {
            $profile->user->update(['status' => AccountStatus::Suspended]);
            $profile->accessRequest?->history()->create([
                'actor_id' => null, 'event' => 'external.expired_suspended',
                'payload' => ['expires_on' => $profile->expires_on->toDateString()],
                'internal' => true,
            ]);
            $this->audit->log('reception.external_account_expired', $profile->user,
                new: ['expires_on' => $profile->expires_on->toDateString(),
                      'category' => $profile->category]);
        }
        return $profiles->count();
    }
}
