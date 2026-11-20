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
            'collection.manage','collection.confirm','warehouse_tz.receive','distribution.manage','release.override'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $map = [
            'Arrival Officer' => ['arrival.view','arrival.create','arrival.update','arrival.reconcile','warehouse_tz.receive','collection.confirm'],
            'Customs Officer' => ['arrival.view','customs.view','customs.create_case','customs.update_case','customs.manage_agents'],
            'Distribution Officer' => ['arrival.view','distribution.manage','collection.manage'],
            'TZ Operations Manager' => ['arrival.view','arrival.create','arrival.update','arrival.reconcile','arrival.all_branches','customs.view','customs.create_case','customs.update_case','customs.confirm_release','customs.manage_agents','collection.manage','collection.confirm','warehouse_tz.receive','distribution.manage','release.override'],
        ];
        foreach ($map as $role => $perms) { Role::findOrCreate($role, 'web')->syncPermissions($perms); }
    }
}
