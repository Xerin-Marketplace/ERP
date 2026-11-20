<?php

namespace Modules\Identity\Services;

use App\Enums\AccountStatus;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Modules\Identity\Models\AccessInvitation;

/**
 * Account lifecycle — activation, suspension, deactivation and the
 * session/token revocation that must accompany security transitions.
 */
class UserLifecycleService
{
    public function __construct(private AuditService $audit) {}

    /** Mark email verified + activate — for admin-verified accounts that skip the email link. */
    public function verifyAndActivate(User $user, int $actorId): User
    {
        return DB::transaction(function () use ($user, $actorId) {
            if (! $user->email_verified_at) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
            if ($user->status !== \App\Enums\AccountStatus::Active) {
                $user = $this->activate($user, $actorId);
            }
            $this->audit->log('user.verified_activated', $user, metadata: ['actor_id' => $actorId]);
            return $user;
        });
    }

    /** Soft-delete a user after revoking all access. Self + super-admin protected. */
    public function delete(User $user, int $actorId, ?string $reason = null): void
    {
        abort_if($user->id === $actorId, 422, 'You cannot delete your own account.');
        abort_if($user->hasRole('super-admin'), 422, 'super-admin accounts cannot be deleted.');
        DB::transaction(function () use ($user, $actorId, $reason) {
            $this->revokeAccess($user, $actorId, 'deleted');
            $user->update(['status' => \App\Enums\AccountStatus::Deactivated]);
            $this->audit->log('user.deleted', $user,
                metadata: ['actor_id' => $actorId, 'reason' => $reason]);
            $user->securityEvents()->create([
                'type' => 'account_deleted', 'severity' => 'warning',
                'metadata' => ['by' => $actorId, 'reason' => $reason],
                'ip_address' => request()->ip(),
            ]);
            $user->delete();
        });
    }

    public function activate(User $user, int $actorId): User
    {
        return $this->transition($user, AccountStatus::Active, $actorId, 'user.activated');
    }

    public function suspend(User $user, int $actorId, ?string $reason = null): User
    {
        $user = $this->transition($user, AccountStatus::Suspended, $actorId, 'user.suspended', $reason);
        $this->revokeAccess($user, $actorId, 'suspended');

        return $user;
    }

    public function deactivate(User $user, int $actorId, ?string $reason = null): User
    {
        $user = $this->transition($user, AccountStatus::Deactivated, $actorId, 'user.deactivated', $reason);
        $this->revokeAccess($user, $actorId, 'deactivated');

        return $user;
    }

    private function transition(User $user, AccountStatus $to, int $actorId, string $action, ?string $reason = null): User
    {
        return DB::transaction(function () use ($user, $to, $actorId, $action, $reason) {
            $old = $user->status;
            abort_if($old === $to, 422, "User is already {$to->value}.");

            $user->update(['status' => $to]);
            $this->audit->log($action, $user,
                old: ['status' => $old?->value], new: ['status' => $to->value],
                metadata: ['actor_id' => $actorId, 'reason' => $reason]);

            $user->securityEvents()->create([
                'type' => $action,
                'severity' => $to === AccountStatus::Active ? 'info' : 'warning',
                'metadata' => ['by' => $actorId, 'reason' => $reason],
                'ip_address' => request()->ip(),
            ]);

            return $user;
        });
    }

    /**
     * Kill every live access path: Sanctum API tokens, web sessions (DB driver)
     * and any outstanding activation invitations.
     */
    public function revokeAccess(User $user, int $actorId, string $cause): void
    {
        $user->tokens()->delete();

        if (config('session.driver') === 'database') {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        AccessInvitation::where('user_id', $user->id)
            ->whereNull('used_at')->whereNull('revoked_at')
            ->update(['revoked_at' => now(), 'revoked_by' => $actorId]);

        $user->securityEvents()->create([
            'type' => 'access_revoked',
            'severity' => 'warning',
            'metadata' => ['cause' => $cause, 'by' => $actorId],
            'ip_address' => request()->ip(),
        ]);

        $this->audit->log('user.access_revoked', $user, metadata: ['cause' => $cause]);
    }
}
