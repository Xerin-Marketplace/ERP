<?php

namespace Modules\Identity\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\AuditService;
use Modules\Organization\Models\BranchAdministrator;

/**
 * Delegated-administration enforcement — the server-side source of truth
 * for the Super Admin / Branch Admin hierarchy.
 *
 * Super Admin  = global authority (hasRole('super-admin')), never branch-scoped.
 * Branch Admin = role 'branch-admin' + an ACTIVE branch_administrators row
 *                pinning the user to exactly one branch plus explicit
 *                delegable role/permission lists.
 *
 * Hard rules enforced here (never in the UI):
 *  1. A Branch Admin can only assign roles in their delegable_roles list.
 *  2. A Branch Admin can only grant permissions in delegable_permissions.
 *  3. 'super-admin' and 'branch-admin' can NEVER be assigned by a Branch Admin.
 *  4. A Branch Admin can only act on users whose effective branch equals theirs.
 *  5. No user may escalate their own delegation (extend delegable_* lists,
 *     change own branch, or grant themselves a role above their tier).
 *  6. Suspended / expired / revoked delegations grant ZERO authority.
 *  7. Only super-admin may create, modify, suspend or revoke a Branch Admin.
 */
class AdminDelegationService
{
    public const GLOBAL_ROLES = ['super-admin', 'branch-admin'];

    public function __construct(private AuditService $audit) {}

    /* ---------- queries ---------- */

    public function delegationFor(User $user): ?BranchAdministrator
    {
        return BranchAdministrator::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->first();
    }

    /** Branch the user administers, or null. */
    public function adminBranchId(User $user): ?int
    {
        return $this->delegationFor($user)?->branch_id;
    }

    public function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function isBranchAdmin(User $user): bool
    {
        return $this->delegationFor($user) !== null && $user->hasRole('branch-admin');
    }

    /* ---------- enforcement ---------- */

    /** Effective branch of a user: assignment branch, else primary branch_id. */
    public function effectiveBranchId(User $user): ?int
    {
        return $this->delegationFor($user)?->branch_id ?? $user->branch_id;
    }

    /**
     * Assert $actor may administrate $target.
     * Super-admin: always. Branch-admin: same branch only, and never a super-admin.
     */
    public function assertCanActOn(User $actor, User $target): void
    {
        if ($this->isSuperAdmin($actor)) {
            return;
        }
        if (! $this->isBranchAdmin($actor)) {
            throw ValidationException::withMessages(['user' => 'No delegated administrative authority.']);
        }
        if ($this->isSuperAdmin($target)) {
            throw ValidationException::withMessages(['user' => 'A Branch Admin cannot act on a Super Admin.']);
        }
        $myBranch = $this->adminBranchId($actor);
        if ($myBranch === null || $this->effectiveBranchId($target) !== $myBranch) {
            throw ValidationException::withMessages(['user' => 'User is outside your branch.']);
        }
    }

    /** Assert $actor may assign $roleName to $target. */
    public function assertCanAssignRole(User $actor, string $roleName, User $target): void
    {
        app(RoleAssignmentGuard::class)->assertCanAssign($actor, $roleName, $target);
        if ($this->isSuperAdmin($actor)) {
            return;
        }
        if (! $this->isBranchAdmin($actor)) {
            throw ValidationException::withMessages(['role' => 'Only an administrator can assign roles.']);
        }
        if (in_array($roleName, self::GLOBAL_ROLES, true)) {
            throw ValidationException::withMessages(['role' => "A Branch Admin cannot assign '{$roleName}'."]);
        }
        $del = $this->delegationFor($actor);
        if (! in_array($roleName, $del->delegable_roles ?? [], true)) {
            throw ValidationException::withMessages(['role' => "Role '{$roleName}' is outside your delegation."]);
        }
        $this->assertCanActOn($actor, $target);
    }

    /** Assert $actor may grant $permission to a role/user under their authority. */
    public function assertCanGrantPermission(User $actor, string $permission, User $target): void
    {
        $this->assertCanActOn($actor, $target);
        if ($this->isSuperAdmin($actor)) {
            return;
        }
        $del = $this->delegationFor($actor);
        if (! in_array($permission, $del->delegable_permissions ?? [], true)) {
            throw ValidationException::withMessages(['permission' => "Permission '{$permission}' is outside your delegation."]);
        }
    }

    /** Assert $actor may administrate the given branch itself. */
    public function assertBranchScope(User $actor, ?int $branchId): void
    {
        if ($this->isSuperAdmin($actor)) {
            return;
        }
        if (! $this->isBranchAdmin($actor) || $this->adminBranchId($actor) !== $branchId) {
            throw ValidationException::withMessages(['branch' => 'Branch is outside your authority.']);
        }
    }

    /* ---------- mutation (super-admin only) ---------- */

    /** Create or refresh a Branch Admin delegation. Super-admin only. */
    public function assignBranchAdmin(User $actor, User $target, int $branchId, array $delegableRoles = [], array $delegablePerms = [], ?Carbon $expiresAt = null): BranchAdministrator
    {
        if (! $this->isSuperAdmin($actor)) {
            throw ValidationException::withMessages(['user' => 'Only a Super Admin can delegate branch administration.']);
        }
        // Global roles may never appear in a delegation list.
        $del = BranchAdministrator::updateOrCreate(
            ['user_id' => $target->id],
            ['branch_id' => $branchId, 'assigned_by' => $actor->id,
             'delegable_roles' => array_values(array_diff($delegableRoles, self::GLOBAL_ROLES)),
             'delegable_permissions' => array_values($delegablePerms),
             'status' => 'active', 'expires_at' => $expiresAt,
             'revoked_at' => null, 'revoked_by' => null, 'suspended_at' => null, 'suspended_by' => null]
        );
        if (! $target->hasRole('branch-admin')) {
            $target->assignRole('branch-admin');
        }
        $this->audit->log('admin.branch_admin.assigned', $del, null, ['branch_id' => $branchId, 'target_user' => $target->id]);
        return $del;
    }

    public function suspend(User $actor, BranchAdministrator $del): void
    {
        $this->assertSuperAdmin($actor);
        $del->update(['status' => 'suspended', 'suspended_at' => now(), 'suspended_by' => $actor->id]);
        $this->audit->log('admin.branch_admin.suspended', $del);
    }

    public function reactivate(User $actor, BranchAdministrator $del): void
    {
        $this->assertSuperAdmin($actor);
        $del->update(['status' => 'active', 'suspended_at' => null, 'suspended_by' => null]);
        $this->audit->log('admin.branch_admin.reactivated', $del);
    }

    public function revoke(User $actor, BranchAdministrator $del): void
    {
        $this->assertSuperAdmin($actor);
        $del->update(['revoked_at' => now(), 'revoked_by' => $actor->id]);
        $del->user?->removeRole('branch-admin');
        $this->audit->log('admin.branch_admin.revoked', $del);
    }

    /** Super-admin role removal — protect the last active super-admin. */
    public function assertNotLastSuperAdmin(User $target): void
    {
        if (! $target->hasRole('super-admin')) {
            return;
        }
        $count = User::role('super-admin')->where('status', 'active')->count();
        if ($count <= 1) {
            throw ValidationException::withMessages(['role' => 'Cannot remove the last active Super Admin.']);
        }
    }

    private function assertSuperAdmin(User $actor): void
    {
        if (! $this->isSuperAdmin($actor)) {
            throw ValidationException::withMessages(['user' => 'Super Admin authority required.']);
        }
    }
}
