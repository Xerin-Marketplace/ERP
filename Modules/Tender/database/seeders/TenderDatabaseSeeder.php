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
        // Spec §16: tender work sits inside "Sales and CRM".
        Role::firstOrCreate(['name' => 'Sales and CRM', 'guard_name' => 'web'])
            ->givePermissionTo(self::PERMISSIONS);
    }
}
