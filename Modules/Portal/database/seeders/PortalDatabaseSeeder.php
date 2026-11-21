<?php
namespace Modules\Portal\Database\Seeders;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\{Permission, Role};

class PortalDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['portal.access','portal.staff','complaints.view','complaints.investigate','complaints.resolve',
            'claims.view','claims.create','claims.investigate','claims.approve','claims.settle','privacy.manage'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $map = [
            'Customer' => ['portal.access'],
            'Customer Care' => ['portal.staff','complaints.view','complaints.investigate','complaints.resolve','claims.view','claims.investigate','claims.approve','claims.settle','privacy.manage'],
        ];
        foreach ($map as $role => $perms) { Role::findOrCreate($role, 'web')->syncPermissions($perms); }
    }
}
