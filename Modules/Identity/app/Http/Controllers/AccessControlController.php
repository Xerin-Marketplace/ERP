<?php

namespace Modules\Identity\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Identity\Models\AccessInvitation;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Department;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Phase 2 — Access Control administration surface:
 * pending invitations, permission matrix, permission browser,
 * branch/department access overview and access-change history.
 */
class AccessControlController extends Controller
{
    use AuthorizesRequests;

    /* ------------------------- Invitations ------------------------- */

    public function invitations()
    {
        $pending = AccessInvitation::with('user:id,name,email,status')
            ->whereNull('used_at')->whereNull('revoked_at')
            ->latest()->paginate(15);

        return view('identity::invitations.index', ['invitations' => $pending]);
    }

    public function revokeInvitation(AccessInvitation $invitation, Request $request)
    {
        abort_unless($request->user()->can('users.invite'), 403);
        abort_if($invitation->used_at, 422, 'Invitation already used.');

        $invitation->update(['revoked_at' => now()]);
        app(\App\Services\AuditService::class)->log('invitation.revoked', $invitation->user);

        return back()->with('success', 'Invitation revoked.');
    }

    /* ------------------------- Permission matrix ------------------------- */

    public function matrix()
    {
        $roles = Role::with('permissions:id,name')->orderBy('name')->get();
        $groups = $this->groupedPermissions();

        return view('identity::permissions.matrix', [
            'roles' => $roles,
            'groups' => $groups,
            'protected' => \Modules\Identity\Database\Seeders\IdentityDatabaseSeeder::PROTECTED_ROLES,
        ]);
    }

    public function updateMatrix(Role $role, Request $request)
    {
        abort_unless($request->user()->can('permissions.manage'), 403);
        abort_if(in_array($role->name, \Modules\Identity\Database\Seeders\IdentityDatabaseSeeder::PROTECTED_ROLES, true),
            403, 'Protected roles cannot be modified.');

        $data = $request->validate(['permissions' => ['array'], 'permissions.*' => ['string', 'exists:permissions,name']]);

        // Privilege-escalation guard: cannot grant what you don't hold yourself.
        if (! $request->user()->hasRole('super-admin')) {
            $overreach = collect($data['permissions'] ?? [])
                ->reject(fn ($p) => $request->user()->can($p));
            if ($overreach->isNotEmpty()) {
                return back()->withErrors(['permissions' =>
                    'You cannot grant permissions you do not hold: '.$overreach->implode(', ')]);
            }
        }

        DB::transaction(function () use ($role, $data) {
            $old = $role->permissions->pluck('name')->all();
            $role->syncPermissions($data['permissions'] ?? []);
            app(\App\Services\AuditService::class)->log('role.permissions_synced', $role,
                old: ['permissions' => $old], new: ['permissions' => $data['permissions'] ?? []]);
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', "Permissions updated for {$role->name}.");
    }

    /* ------------------------- Permission browser ------------------------- */

    public function permissions(Request $request)
    {
        $groups = collect($this->groupedPermissions())
            ->when($request->filled('search'), fn ($g) => $g->map(fn ($perms) =>
                collect($perms)->filter(fn ($p) => str_contains($p, $request->input('search')))->values()->all()
            )->filter(fn ($perms) => $perms !== []));

        return view('identity::permissions.index', ['groups' => $groups]);
    }

    /* ------------------------- Access administration ------------------------- */

    public function access(Request $request)
    {
        $branches = Branch::with(['administrators' => fn ($q) => $q->active()->with('user:id,name,email'),
                                  'users:id,name,email,branch_id', 'departments'])
            ->orderBy('name')->get();

        $history = AuditLog::with('user:id,name')
            ->where('action', 'like', 'user.%')->orWhere('action', 'like', 'role.%')
            ->orWhere('action', 'like', 'invitation.%')->orWhere('action', 'like', 'branch.administrator%')
            ->latest()->limit(50)->get();

        return view('identity::access.index', [
            'branches' => $branches,
            'history' => $history,
        ]);
    }

    private function groupedPermissions(): array
    {
        return Permission::orderBy('name')->get(['id', 'name'])
            ->groupBy(fn ($p) => explode('.', $p->name)[0])
            ->map(fn ($g) => $g->pluck('name')->sort()->values()->all())
            ->all();
    }
}
