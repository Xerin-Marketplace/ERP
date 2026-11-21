<?php

namespace Modules\Commercial\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CommercialDatabaseSeeder extends Seeder
{
    public const PERMISSIONS = [
        'commercial.view', 'commercial.all_branches', 'commercial.handover.view',
        'customer.view', 'customer.create', 'customer.update', 'customer.manage',
        'lead.view', 'lead.create', 'lead.update', 'lead.manage',
        'inquiry.view', 'inquiry.create', 'inquiry.update', 'inquiry.assess', 'inquiry.assign',
        'pricing.view', 'pricing.manage', 'pricing.approve',
        'quotation.view', 'quotation.create', 'quotation.update', 'quotation.approve', 'quotation.send',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // Sales Officer — operational commercial role
        $sales = Role::firstOrCreate(['name' => 'Sales and CRM', 'guard_name' => 'web']);
        $sales->syncPermissions([
            'commercial.view', 'customer.view', 'customer.create', 'customer.update',
            'lead.view', 'lead.create', 'lead.update',
            'inquiry.view', 'inquiry.create', 'inquiry.update', 'inquiry.assess',
            'pricing.view', 'quotation.view', 'quotation.create', 'quotation.update', 'quotation.send',
        ]);

        // Sales Manager — approves quotations and rate cards
        $mgr = Role::firstOrCreate(['name' => 'Sales and CRM', 'guard_name' => 'web']);
        $mgr->syncPermissions(array_merge($sales->permissions->pluck('name')->all(), [
            'quotation.approve', 'pricing.manage', 'pricing.approve',
            'inquiry.assign', 'customer.manage', 'lead.manage', 'commercial.handover.view',
        ]));

        // Super-admin baseline gets everything
        Role::where('name', 'super-admin')->first()?->givePermissionTo(self::PERMISSIONS);
    }
}
