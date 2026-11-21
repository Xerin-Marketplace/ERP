<?php
namespace Modules\Arrival\Database\Seeders;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\{Permission, Role};

class ArrivalDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['arrival.view','arrival.create','arrival.update','arrival.reconcile','arrival.all_branches',
            'customs.view','customs.create_case','customs.update_case','customs.confirm_release','customs.manage_agents',
            'collection.manage','collection.confirm','warehouse_tz.receive','distribution.manage','release.override',
            'release.view','release.request','release.approve','storage.view','storage.manage'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $map = [
            'Tanzania Receiving' => ['arrival.view','arrival.create','arrival.update','arrival.reconcile','warehouse_tz.receive','collection.confirm','release.view','storage.view'],
            'Clearance' => ['arrival.view','customs.view','customs.create_case','customs.update_case','customs.confirm_release','customs.manage_agents'],
            'Distribution Centre Officer' => ['arrival.view','distribution.manage','collection.manage','release.view','release.request','storage.manage'],
            'Release Approver' => ['release.view','release.approve','release.override','arrival.all_branches'],
        ];
        foreach ($map as $role => $perms) { Role::findOrCreate($role, 'web')->syncPermissions($perms); }
    }
}
