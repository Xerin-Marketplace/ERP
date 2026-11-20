<?php
namespace Modules\Cargo\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Cargo\Models\{Warehouse, WarehouseLocation};
use Spatie\Permission\Models\{Permission, Role};

class CargoDatabaseSeeder extends Seeder
{
    public const PERMISSIONS = [
        'cargo.dashboard.view', 'cargo.all_branches',
        'cargo.pre_alerts.view', 'cargo.pre_alerts.create', 'cargo.pre_alerts.update',
        'cargo.receiving.view', 'cargo.receiving.create', 'cargo.receiving.confirm', 'cargo.receiving.manage_discrepancies',
        'cargo.packages.view', 'cargo.packages.create', 'cargo.packages.update', 'cargo.packages.scan', 'cargo.packages.print_labels',
        'cargo.weighing.view', 'cargo.weighing.create', 'cargo.weighing.reweigh',
        'cargo.inspection.view', 'cargo.inspection.perform', 'cargo.inspection.approve',
        'cargo.packaging.view', 'cargo.packaging.manage',
        'warehouse.view', 'warehouse.manage', 'warehouse.locations.manage', 'warehouse.movements.create', 'warehouse.movements.approve',
        'cargo.incidents.view', 'cargo.incidents.manage',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $p) Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);

        $officer = Role::firstOrCreate(['name' => 'Cargo Receiving Officer', 'guard_name' => 'web']);
        $officer->syncPermissions(['cargo.dashboard.view','cargo.pre_alerts.view','cargo.pre_alerts.create',
            'cargo.receiving.view','cargo.receiving.create','cargo.packages.view','cargo.packages.scan',
            'cargo.packages.print_labels','cargo.weighing.view','cargo.weighing.create','warehouse.view',
            'warehouse.movements.create','cargo.incidents.view']);

        $whOfficer = Role::firstOrCreate(['name' => 'Warehouse Officer', 'guard_name' => 'web']);
        $whOfficer->syncPermissions(['cargo.dashboard.view','cargo.packages.view','cargo.packages.scan',
            'cargo.packages.print_labels','cargo.weighing.view','warehouse.view','warehouse.movements.create','cargo.incidents.view']);

        $inspector = Role::firstOrCreate(['name' => 'Cargo Inspector', 'guard_name' => 'web']);
        $inspector->syncPermissions(['cargo.dashboard.view','cargo.packages.view','cargo.packages.scan',
            'cargo.inspection.view','cargo.inspection.perform','cargo.weighing.view','warehouse.view','cargo.incidents.view']);

        $mgr = Role::firstOrCreate(['name' => 'Warehouse Manager', 'guard_name' => 'web']);
        $mgr->syncPermissions(self::PERMISSIONS);

        Role::where('name', 'super-admin')->first()?->givePermissionTo(self::PERMISSIONS);

        // Default Dubai warehouse + starter location layout (idempotent)
        $dxb = \Modules\Organization\Models\Branch::where('code', 'DXB')->first();
        if ($dxb) {
            $wh = Warehouse::firstOrCreate(['code' => 'DXB-MAIN'], ['name' => 'Dubai Main Warehouse', 'branch_id' => $dxb->id]);
            foreach ([['RCV-01','receiving'],['INS-01','inspection'],['HOLD-01','hold'],['RPK-01','repacking'],['DSP-01','dispatch'],
                      ['A-01-01','storage'],['A-01-02','storage'],['B-01-01','storage']] as [$code, $type]) {
                WarehouseLocation::firstOrCreate(['warehouse_id' => $wh->id, 'code' => $code],
                    ['name' => $code, 'zone' => $code[0], 'type' => $type, 'capacity' => $type === 'storage' ? 50 : null]);
            }
        }
    }
}
