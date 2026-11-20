<?php

namespace Modules\Tender\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TenderDatabaseSeeder extends Seeder
{
    public const PERMISSIONS = [
        'tenders.view', 'tenders.create', 'tenders.assess', 'tenders.decide',
        'tenders.boq.manage', 'tenders.submit', 'tenders.evaluate', 'tenders.award',
        'tenders.contract.manage', 'tenders.reports.view', 'tenders.manage',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        Role::where('name', 'super-admin')->first()?->givePermissionTo(self::PERMISSIONS);
        Role::firstOrCreate(['name' => 'tender-manager', 'guard_name' => 'web'])
            ->syncPermissions(['tenders.view','tenders.create','tenders.assess','tenders.decide',
                'tenders.boq.manage','tenders.submit','tenders.evaluate','tenders.award',
                'tenders.contract.manage','tenders.reports.view']);
        Role::firstOrCreate(['name' => 'tender-officer', 'guard_name' => 'web'])
            ->syncPermissions(['tenders.view','tenders.create','tenders.assess','tenders.boq.manage']);
        Role::firstOrCreate(['name' => 'quantity-surveyor', 'guard_name' => 'web'])
            ->syncPermissions(['tenders.view','tenders.boq.manage']);
        Role::firstOrCreate(['name' => 'compliance-officer', 'guard_name' => 'web'])
            ->syncPermissions(['tenders.view','tenders.assess']);
    }
}
