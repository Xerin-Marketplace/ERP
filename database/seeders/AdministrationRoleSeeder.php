<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Administration role power-matrix seeder.
 *
 * Runs LAST — after every module seeder — and grants each governance role a
 * curated permission set matched by PATTERN, so permissions introduced by any
 * future module are picked up automatically on re-seed.
 *
 * Idempotent: firstOrCreate + syncPermissions only; never deletes roles and
 * never touches module-owned roles (receptionist, hr-administrator, ...).
 *
 * NOTE: permissions describe what a role CAN do; AdminDelegationService still
 * decides WHERE (branch/dept scope) and WHETHER a branch-admin may pass a
 * permission on to someone else. Seeding broad perms is safe because
 * delegation + branch scoping are enforced server-side.
 */
class AdministrationRoleSeeder extends Seeder
{
    /** Roles whose full permission sync is owned by this seeder. */
    private const GOVERNANCE_ROLES = [
        'super-admin', 'admin', 'branch-admin',
        'department-manager', 'department-admin', 'system-auditor',
    ];

    public function run(): void
    {
        // Permissions may have just been created by module seeders — drop
        // Spatie's cache so syncPermissions resolves them.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $all = Permission::pluck('name');

        $matrix = [
            // Global authority — everything, no exceptions.
            'super-admin' => $all->all(),

            // Legacy admin — broad operational + admin console, no global gov.
            'admin' => $this->except($all, [
                'admin.delegation.manage', 'admin.settings.global',
            ]),

            // Branch governor — everything operational inside a branch,
            // minus global-governance surfaces (enforced anyway).
            'branch-admin' => $this->except($all, [
                'admin.delegation.manage', 'admin.settings.global',
                'admin.branch.manage',
                'system.manage', 'settings.manage',
                'permissions.view', 'permissions.manage',
                'roles.view', 'roles.create', 'roles.update', 'roles.delete',
                'role.view', 'role.create', 'role.update', 'role.delete',
            ], denyPrefixes: ['sysadmin.'], denySuffixes: ['.impersonate']),

            // Department manager — full read visibility + people-ops manage.
            'department-manager' => $this->only($all,
                suffixes: ['.view', '.view_branch', '.view_department', '.approve', '.review', '.export'],
                prefixes: [
                    'hr.', 'leave.', 'attendance.', 'overtime.', 'shift.',
                    'performance.', 'disciplinary.', 'employee.', 'department.',
                    'recruitment.', 'users.view',
                ]),

            // Department admin — departmental settings + read access.
            'department-admin' => $this->only($all,
                suffixes: ['.view'],
                prefixes: ['department.', 'employee.', 'attendance.', 'leave.', 'shift.']),

            // Read-only auditor — views + every audit surface.
            'system-auditor' => $this->only($all,
                suffixes: ['.view', '.export'],
                contains: ['audit']),
        ];

        foreach ($matrix as $role => $perms) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'])
                ->syncPermissions($perms);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** All permissions minus explicit denies / denied prefixes / suffixes. */
    private function only(
        $all,
        array $deny = [],
        array $denyPrefixes = [],
        array $denySuffixes = [],
        array $suffixes = [],
        array $prefixes = [],
        array $contains = [],
    ): array {
        return $all->filter(function ($p) use (
            $deny, $denyPrefixes, $denySuffixes, $suffixes, $prefixes, $contains
        ) {
            if (in_array($p, $deny, true)) return false;
            foreach ($denyPrefixes as $x) if (str_starts_with($p, $x)) return false;
            foreach ($denySuffixes as $x) if (str_ends_with($p, $x)) return false;
            foreach ($prefixes as $x) if (str_starts_with($p, $x)) return true;
            foreach ($suffixes as $x) if (str_ends_with($p, $x)) return true;
            foreach ($contains as $x) if (str_contains($p, $x)) return true;
            return false;
        })->values()->all();
    }

    private function except($all, array $deny, array $denyPrefixes = [], array $denySuffixes = []): array
    {
        return $all->reject(function ($p) use ($deny, $denyPrefixes, $denySuffixes) {
            if (in_array($p, $deny, true)) return true;
            foreach ($denyPrefixes as $x) if (str_starts_with($p, $x)) return true;
            foreach ($denySuffixes as $x) if (str_ends_with($p, $x)) return true;
            return false;
        })->values()->all();
    }
}
