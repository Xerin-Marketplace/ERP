<?php
namespace Modules\Shipment\Database\Seeders;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\{Permission, Role};

class ShipmentDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['shipment.view','shipment.create','shipment.update','shipment.allocate','shipment.split',
            'shipment.booking.manage','shipment.booking.confirm','shipment.awb.create','shipment.awb.issue',
            'shipment.manifest.manage','shipment.manifest.approve','shipment.dispatch.manage','shipment.handover.confirm',
            'shipment.airline.manage','shipment.flight.manage','shipment.exception.manage','shipment.all_branches'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $map = [
            'Dubai Warehouse and Cargo Operations' => ['shipment.view','shipment.create','shipment.update','shipment.allocate','shipment.split','shipment.booking.manage','shipment.booking.confirm','shipment.airline.manage','shipment.flight.manage','shipment.awb.create','shipment.awb.issue','shipment.manifest.manage','shipment.manifest.approve','shipment.dispatch.manage','shipment.handover.confirm','shipment.exception.manage','shipment.all_branches'],
        ];
        foreach ($map as $role => $perms) { Role::findOrCreate($role, 'web')->syncPermissions($perms); }
    }
}
