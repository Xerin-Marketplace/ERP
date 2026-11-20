<?php

namespace Modules\Identity\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class IdentityDatabaseSeeder extends Seeder
{
    /**
     * Legacy names kept granted for backward compatibility; routes use the
     * canonical users-dot and roles-dot names from Phase 2 onward.
     */
    public const LEGACY_PERMISSIONS = [
        'user.view', 'user.create', 'user.update', 'user.suspend',
        'user.activate', 'user.deactivate', 'user.sessions.revoke',
        'user.assignments.view', 'user.assignments.manage',
        'role.view', 'role.create', 'role.update', 'role.delete', 'role.assign',
    ];

    public const PERMISSIONS = [
        'identity.view', 'identity.manage',
        'registration.view', 'registration.review', 'registration.request-info',
        'registration.approve', 'registration.reject', 'registration.assign',
        'registration.provision',
        'users.view', 'users.create', 'users.update', 'users.suspend',
        'users.activate', 'users.deactivate', 'users.sessions.revoke',
        'users.invite', 'users.reset_password', 'users.delete',
        'users.assignments.view', 'users.assignments.manage',
        'users.assign_roles', 'users.assign_permissions',
        'roles.view', 'roles.create', 'roles.update', 'roles.delete', 'roles.assign',
        'permissions.view', 'permissions.manage',
        'access.audit.view',
        'identity.audit.view',
    ];

    /** Forward-looking groups seeded now so the permission matrix is complete. */
    public const FUTURE_MODULE_PERMISSIONS = [
        'reception.dashboard.view', 'reception.visitors.create',
        'reception.visitors.view', 'reception.requests.manage',
        'reception.attendance.view', 'reception.attendance.view_daily',
        'reception.attendance.view_department', 'reception.attendance.export',
        'reception.employees.view_directory',
        'reception.tickets.create', 'reception.tickets.view',
        'reception.tickets.view_branch', 'reception.tickets.track',
        'reception.tickets.comment', 'reception.tickets.attachments.upload',
        'reception.it_availability.view',
        'reception.reports.view', 'reception.reports.export',
        'it.dashboard.view', 'it.availability.manage',
    ];

    /**
     * role => permissions (null = all module permissions).
     * super-admin is handled globally — never seeded grants here.
     */
    public const ROLES = [
        'it-administrator' => self::PERMISSIONS,
        'it-officer' => [
            'identity.view', 'registration.view', 'registration.review',
            'registration.request-info', 'registration.approve', 'registration.reject',
            'users.view', 'users.assignments.view',
        ],
        'group-administrator' => [
            'identity.view', 'registration.view', 'users.view', 'roles.view',
            'users.assign_roles', 'users.invite', 'users.reset_password', 'users.delete',
            'permissions.view', 'access.audit.view', 'identity.audit.view',
        ],
        'company-administrator' => [
            'identity.view', 'registration.view', 'users.view', 'users.assignments.view',
        ],
        'branch-manager' => [
            'identity.view', 'users.view', 'users.assignments.view',
        ],
        'department-manager' => [
            'identity.view', 'users.view',
        ],
        'employee' => [],
        'hr-administrator' => [
            'identity.view', 'users.view', 'users.assignments.view',
        ],
        'hr-officer' => [
            'identity.view', 'users.view',
        ],
        'receptionist' => [
            'identity.view', 'users.view',
            'reception.dashboard.view', 'reception.visitors.create',
            'reception.visitors.view', 'reception.requests.manage',
            'reception.attendance.view', 'reception.attendance.view_daily',
            'reception.attendance.view_department',
            'reception.employees.view_directory',
            'reception.tickets.create', 'reception.tickets.view',
            'reception.tickets.view_branch', 'reception.tickets.track',
            'reception.tickets.comment', 'reception.tickets.attachments.upload',
            'reception.it_availability.view',
        ],
        'auditor' => [
            'identity.view', 'registration.view', 'users.view', 'roles.view',
            'permissions.view', 'access.audit.view', 'identity.audit.view',
        ],
        'management-viewer' => [
            'identity.view', 'registration.view', 'users.view', 'identity.audit.view',
        ],
    ];

    /** Roles that can never be modified or assigned through the UI. */
    public const PROTECTED_ROLES = ['super-admin'];

    public function run(): void
    {
        DB::transaction(function () {
            foreach (array_merge(self::LEGACY_PERMISSIONS, self::PERMISSIONS, self::FUTURE_MODULE_PERMISSIONS) as $name) {
                Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            }
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            foreach (self::ROLES as $roleName => $permissions) {
                $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
                if ($permissions) {
                    $role->givePermissionTo($permissions);
                }
            }

            if ($super = Role::where('name', 'super-admin')->first()) {
                $super->givePermissionTo(array_merge(self::LEGACY_PERMISSIONS, self::PERMISSIONS, self::FUTURE_MODULE_PERMISSIONS));
            }
        });
    }
}
