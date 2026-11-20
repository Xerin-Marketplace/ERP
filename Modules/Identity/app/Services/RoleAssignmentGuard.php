<?php

namespace Modules\Identity\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;

/**
 * Privilege-escalation protection for role assignment.
 *
 * Rules:
 *  - Protected roles (super-admin) can only be granted by a super-admin,
 *    and never removed from the last super-admin.
 *  - Privileged roles (group-administrator, company-administrator) can only
 *    be granted by a principal holding that same privilege tier.
 *  - Nobody may elevate their own account beyond its current tier.
 */
class RoleAssignmentGuard
{
    /** Privileged tiers, strongest first. */
    private const PRIVILEGED = [
        'super-admin' => 100,
        'group-administrator' => 80,
        'company-administrator' => 70,
    ];

    /** Highest privilege tier the user holds (0 = none). */
    public function tierOf(User $user): int
    {
        foreach (self::PRIVILEGED as $role => $tier) {
            if ($user->hasRole($role)) {
                return $tier;
            }
        }

        return 0;
    }

    /** Throws ValidationException when the assignment is not permitted. */
    public function assertCanAssign(User $actor, string $roleName, User $target): void
    {
        if (in_array($roleName, IdentityDatabaseSeeder::PROTECTED_ROLES, true)
            && ! $actor->hasRole('super-admin')) {
            throw ValidationException::withMessages([
                'role' => 'Only a Super Admin can assign the super-admin role.',
            ]);
        }

        $required = self::PRIVILEGED[$roleName] ?? 0;

        if ($required > 0 && $this->tierOf($actor) < $required) {
            throw ValidationException::withMessages([
                'role' => "You do not hold the privilege required to assign '{$roleName}'.",
            ]);
        }

        // Self-assignment to a higher tier is always an escalation attempt.
        if ($actor->id === $target->id && $required > $this->tierOf($actor)) {
            throw ValidationException::withMessages([
                'role' => 'You cannot elevate your own privileges.',
            ]);
        }
    }

    /** Roles the actor is allowed to assign (for UI filtering). */
    public function assignableRoles(User $actor, $roles): array
    {
        $tier = $this->tierOf($actor);

        return $roles->filter(fn ($role) => ! in_array($role->name, IdentityDatabaseSeeder::PROTECTED_ROLES, true)
            || $actor->hasRole('super-admin'))
            ->filter(fn ($role) => (self::PRIVILEGED[$role->name] ?? 0) <= $tier)
            ->values()->all();
    }
}
