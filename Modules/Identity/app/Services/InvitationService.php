<?php

namespace Modules\Identity\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Models\AccessInvitation;
use Modules\Identity\Models\RegistrationApplication;

/**
 * Secure account-activation invitations.
 * Tokens are random 64-char secrets, stored hashed (SHA-256), single-use,
 * expiring and revocable — never a plaintext password.
 */
class InvitationService
{
    public const TTL_HOURS = 72;

    /**
     * Generate a new invitation. Revokes any outstanding ones for the user.
     *
     * @return array{invitation: AccessInvitation, token: string}
     */
    public function generate(User $user, ?RegistrationApplication $application = null, ?int $createdBy = null): array
    {
        return DB::transaction(function () use ($user, $application, $createdBy) {
            AccessInvitation::where('user_id', $user->id)
                ->whereNull('used_at')->whereNull('revoked_at')
                ->update(['revoked_at' => now(), 'revoked_by' => $createdBy]);

            $token = Str::random(64);

            $invitation = AccessInvitation::create([
                'user_id' => $user->id,
                'registration_application_id' => $application?->id,
                'email' => $user->email,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addHours(self::TTL_HOURS),
                'created_by' => $createdBy,
            ]);

            return ['invitation' => $invitation, 'token' => $token];
        });
    }

    /** Resolve a plaintext token to a usable invitation or fail loudly. */
    public function resolve(string $token): AccessInvitation
    {
        $invitation = AccessInvitation::where('token_hash', hash('sha256', $token))->first();

        if (! $invitation) {
            throw ValidationException::withMessages(['token' => 'Invalid activation link.']);
        }
        if ($invitation->used_at) {
            throw ValidationException::withMessages(['token' => 'This activation link has already been used.']);
        }
        if ($invitation->revoked_at) {
            throw ValidationException::withMessages(['token' => 'This activation link has been revoked.']);
        }
        if ($invitation->expires_at->isPast()) {
            throw ValidationException::withMessages(['token' => 'This activation link has expired.']);
        }

        return $invitation;
    }

    /**
     * Consume the invitation: set the user's first password and activate.
     */
    public function consume(AccessInvitation $invitation, string $password): User
    {
        return DB::transaction(function () use ($invitation, $password) {
            $invitation = AccessInvitation::lockForUpdate()->findOrFail($invitation->id);
            if (! $invitation->isUsable()) {
                throw ValidationException::withMessages(['token' => 'This activation link is no longer valid.']);
            }

            $user = $invitation->user;
            $user->update([
                'password' => Hash::make($password),
                'email_verified_at' => $user->email_verified_at ?? now(),
                'status' => \App\Enums\AccountStatus::Active,
            ]);

            $invitation->update(['used_at' => now()]);

            return $user;
        });
    }

    public function revoke(AccessInvitation $invitation, int $actorId): void
    {
        $invitation->update(['revoked_at' => now(), 'revoked_by' => $actorId]);
    }
}
