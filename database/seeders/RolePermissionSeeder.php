<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Phase 0 platform permissions. Business modules register
     * their own permissions via their module seeders.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'system.view',
            'system.manage',
            'audit.view',
            'settings.view',
            'settings.manage',
            'users.view',
            'users.manage',
            'files.view',
            'files.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Refresh the registrar cache so freshly-created permissions resolve
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions($permissions);

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions([
            'system.view', 'audit.view', 'settings.view',
            'users.view', 'users.manage', 'users.delete', 'files.view', 'files.manage',
        ]);

        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web'])->syncPermissions(['files.view']);

        User::where('email', 'admin@xerinhub.com')->first()?->assignRole('super-admin');
    }
}
