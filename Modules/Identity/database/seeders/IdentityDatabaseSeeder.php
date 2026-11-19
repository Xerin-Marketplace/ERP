<?php

namespace Modules\Identity\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class IdentityDatabaseSeeder extends Seeder
{
    public const PERMISSIONS = [
        'identity.view', 'identity.manage',
        'registration.view', 'registration.review', 'registration.request-info',
        'registration.approve', 'registration.reject', 'registration.assign',
        'registration.provision',
        'user.view', 'user.create', 'user.update', 'user.suspend',
        'user.activate', 'user.deactivate', 'user.sessions.revoke',
        'user.assignments.view', 'user.assignments.manage',
        'role.view', 'role.create', 'role.update', 'role.delete', 'role.assign',
        'identity.audit.view',
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
            'user.view', 'user.assignments.view',
        ],
        'group-administrator' => [
            'identity.view', 'registration.view', 'user.view', 'role.view', 'identity.audit.view',
        ],
        'company-administrator' => [
            'identity.view', 'registration.view', 'user.view', 'user.assignments.view',
        ],
        'branch-manager' => [
            'identity.view', 'user.view', 'user.assignments.view',
        ],
        'department-manager' => [
            'identity.view', 'user.view',
        ],
        'employee' => [],
        'auditor' => [
            'identity.view', 'registration.view', 'user.view', 'role.view', 'identity.audit.view',
        ],
        'management-viewer' => [
            'identity.view', 'registration.view', 'user.view', 'identity.audit.view',
        ],
    ];

    /** Roles that can never be modified or assigned through the UI. */
    public const PROTECTED_ROLES = ['super-admin'];

    public function run(): void
    {
        DB::transaction(function () {
            foreach (self::PERMISSIONS as $name) {
                Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            }
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            foreach (self::ROLES as $roleName => $permissions) {
                $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
                if ($permissions) {
                    $role->syncPermissions($permissions);
                }
            }

            if ($super = Role::where('name', 'super-admin')->first()) {
                $super->givePermissionTo(self::PERMISSIONS);
            }
        });
    }
}
