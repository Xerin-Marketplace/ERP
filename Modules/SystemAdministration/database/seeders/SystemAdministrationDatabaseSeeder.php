<?php

namespace Modules\SystemAdministration\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SystemAdministrationDatabaseSeeder extends Seeder
{
    public const PERMISSIONS = [
        'sysadmin.dashboard.view',
        'sysadmin.branding.manage',
        'sysadmin.settings.manage',
        'sysadmin.rules.manage',
        'sysadmin.modules.manage',
        'sysadmin.navigation.manage',
        'sysadmin.flags.manage',
        'sysadmin.announcements.manage',
        'sysadmin.integrations.manage',
        'sysadmin.security.manage',
        'sysadmin.audit.view',
        'sysadmin.maintenance.manage',
        'sysadmin.monitoring.view',
        'sysadmin.backups.view',
        'sysadmin.localization.manage',
        'sysadmin.commands.view', 'sysadmin.commands.execute', 'sysadmin.commands.approve',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // super-admin accumulates — never sync (would wipe other modules' grants)
        Role::where('name', 'super-admin')->first()?->givePermissionTo(self::PERMISSIONS);
    }
}
