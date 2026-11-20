<?php
namespace Modules\Delivery\Database\Seeders;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\{Permission, Role};

class DeliveryDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['delivery.view','delivery.create','delivery.update','delivery.dispatch','delivery.returns',
            'delivery.cod','delivery.drivers','delivery.vehicles','delivery.config','delivery.all_branches'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $map = [
            'Dispatch Officer' => ['delivery.view','delivery.create','delivery.update','delivery.dispatch'],
            'Delivery Driver' => ['delivery.view'],
            'Delivery Manager' => ['delivery.view','delivery.create','delivery.update','delivery.dispatch','delivery.returns','delivery.cod','delivery.drivers','delivery.vehicles','delivery.config','delivery.all_branches'],
        ];
        foreach ($map as $role => $perms) { Role::findOrCreate($role, 'web')->syncPermissions($perms); }
        // seed default services + Dar es Salaam zones
        \Modules\Delivery\Models\DeliveryService::firstOrCreate(['code' => 'STD'], ['name' => 'Standard Delivery', 'pricing_method' => 'zone_fixed', 'base_fee' => 5000, 'active' => true]);
        \Modules\Delivery\Models\DeliveryService::firstOrCreate(['code' => 'EXP'], ['name' => 'Express Delivery', 'pricing_method' => 'zone_fixed', 'base_fee' => 12000, 'active' => true]);
        \Modules\Delivery\Models\DeliveryService::firstOrCreate(['code' => 'COL'], ['name' => 'Customer Collection', 'pricing_method' => 'zone_fixed', 'base_fee' => 0, 'active' => true]);
        $tz = \Modules\Organization\Models\Branch::where('code','TZ')->first();
        foreach ([['Dar CBD','Dar es Salaam','Ilala',3000],['Kinondoni','Dar es Salaam','Kinondoni',5000],['Temeke','Dar es Salaam','Temeke',6000],['Arusha','Arusha','Arusha City',15000],['Mwanza','Mwanza','Nyamagana',18000],['Dodoma','Dodoma','Dodoma Urban',12000]] as [$n,$reg,$dist,$fee]) {
            \Modules\Delivery\Models\DeliveryZone::firstOrCreate(['name' => $n, 'district' => $dist], ['region' => $reg, 'fee' => $fee, 'eta' => 'Same/next day', 'branch_id' => $tz?->id, 'active' => true]);
        }
    }
}
