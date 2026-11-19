<?php

namespace Modules\Identity\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleManagementController extends Controller
{
    public function index()
    {
        return view('identity::roles.index', [
            'roles' => Role::withCount('users')->with('permissions:id,name')->orderBy('name')->get(),
            'protected' => IdentityDatabaseSeeder::PROTECTED_ROLES,
        ]);
    }

    public function create()
    {
        return view('identity::roles.form', [
            'role' => null,
            'permissionGroups' => $this->groupedPermissions(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('role.create'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9\-]+$/', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $role = DB::transaction(function () use ($data) {
            $role = Role::create(['name' => strtolower($data['name']), 'guard_name' => 'web']);
            $role->syncPermissions($data['permissions'] ?? []);
            return $role;
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(\App\Services\AuditService::class)->log('role.created', $role, new: $data);

        return redirect()->route('identity.roles.index')->with('success', "Role {$role->name} created.");
    }

    public function edit(Role $role)
    {
        abort_if(in_array($role->name, IdentityDatabaseSeeder::PROTECTED_ROLES, true), 403,
            'Protected roles cannot be modified.');

        return view('identity::roles.form', [
            'role' => $role->load('permissions:id,name'),
            'permissionGroups' => $this->groupedPermissions(),
        ]);
    }

    public function update(Role $role, Request $request)
    {
        abort_unless($request->user()->can('role.update'), 403);
        abort_if(in_array($role->name, IdentityDatabaseSeeder::PROTECTED_ROLES, true), 403,
            'Protected roles cannot be modified.');

        $data = $request->validate(['permissions' => ['nullable', 'array'], 'permissions.*' => ['exists:permissions,name']]);

        $old = $role->permissions->pluck('name')->toArray();
        $role->syncPermissions($data['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        app(\App\Services\AuditService::class)->log('role.permissions_changed', $role,
            old: ['permissions' => $old], new: ['permissions' => $data['permissions'] ?? []]);

        return back()->with('success', "Permissions updated for {$role->name}.");
    }

    public function cloneRole(Role $role, Request $request)
    {
        abort_unless($request->user()->can('role.create'), 403);
        $request->validate(['name' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9\-]+$/', 'unique:roles,name']]);

        $clone = Role::create(['name' => strtolower($request->input('name')), 'guard_name' => 'web']);
        $clone->syncPermissions($role->permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('identity.roles.index')->with('success', "Role cloned as {$clone->name}.");
    }

    public function destroy(Role $role, Request $request)
    {
        abort_unless($request->user()->can('role.delete'), 403);
        abort_if(in_array($role->name, IdentityDatabaseSeeder::PROTECTED_ROLES, true), 403,
            'Protected roles cannot be deleted.');
        abort_if($role->users()->exists(), 422, 'Role still has users — reassign them first.');

        app(\App\Services\AuditService::class)->log('role.deleted', $role, old: ['name' => $role->name]);
        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('identity.roles.index')->with('success', 'Role deleted.');
    }

    private function groupedPermissions(): array
    {
        return Permission::orderBy('name')->get(['id', 'name'])
            ->groupBy(fn ($p) => explode('.', $p->name)[0])
            ->map(fn ($g) => $g->pluck('name')->all())
            ->all();
    }
}
