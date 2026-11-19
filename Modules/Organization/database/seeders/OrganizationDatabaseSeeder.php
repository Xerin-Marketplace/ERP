<?php

namespace Modules\Organization\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\DepartmentDefinition;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class OrganizationDatabaseSeeder extends Seeder
{
    public const PERMISSIONS = [
        'organization.view', 'organization.manage',
        'organization.structure.view', 'organization.audit.view',
        'company.view', 'company.create', 'company.update',
        'company.activate', 'company.suspend', 'company.archive',
        'branch.view', 'branch.create', 'branch.update',
        'branch.activate', 'branch.suspend', 'branch.archive',
        'location.view', 'location.create', 'location.update', 'location.delete',
        'department.view', 'department.create', 'department.update',
        'department.activate', 'department.suspend', 'department.archive',
    ];

    public const DEFINITIONS = [
        ['code' => 'IT', 'name' => 'Information Technology'],
        ['code' => 'HR', 'name' => 'Human Resources'],
        ['code' => 'FIN', 'name' => 'Finance & Accounting'],
        ['code' => 'OPS', 'name' => 'Operations'],
        ['code' => 'PROC', 'name' => 'Procurement'],
        ['code' => 'ADMIN', 'name' => 'Administration'],
        ['code' => 'MKT', 'name' => 'Marketing'],
        ['code' => 'SALES', 'name' => 'Sales'],
        ['code' => 'CS', 'name' => 'Customer Service'],
        ['code' => 'LEGAL', 'name' => 'Legal & Compliance'],
        ['code' => 'PMO', 'name' => 'Project Management'],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            /* Permissions — idempotent */
            foreach (self::PERMISSIONS as $name) {
                Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            }
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            if ($super = Role::where('name', 'super-admin')->first()) {
                $super->givePermissionTo(self::PERMISSIONS);
            }
            if ($admin = Role::where('name', 'admin')->first()) {
                $admin->givePermissionTo([
                    'organization.view', 'organization.structure.view',
                    'company.view', 'branch.view', 'location.view', 'department.view',
                ]);
            }
            if ($manager = Role::where('name', 'manager')->first()) {
                $manager->givePermissionTo([
                    'organization.view', 'organization.structure.view',
                    'company.view', 'branch.view', 'department.view',
                ]);
            }

            /* Department definitions — reusable classifications */
            foreach (self::DEFINITIONS as $def) {
                DepartmentDefinition::firstOrCreate(['code' => $def['code']], $def);
            }

            /* Root company — the group itself */
            $group = Company::firstOrCreate(
                ['code' => 'XG'],
                [
                    'legal_name' => 'Xerin Group Company Limited',
                    'trading_name' => 'Xerin Group',
                    'company_type' => 'holding',
                    'country' => 'Tanzania',
                    'region' => 'Dar es Salaam',
                    'city' => 'Dar es Salaam',
                    'currency' => 'TZS',
                    'timezone' => 'Africa/Dar_es_Salaam',
                    'status' => 'active',
                ]
            );

            /* Attach the seeded HQ branch to the group + set type */
            if ($hq = Branch::where('code', 'HQ')->first()) {
                $hq->update([
                    'company_id' => $group->id,
                    'branch_type' => 'head_office',
                    'country' => 'Tanzania',
                    'region' => 'Dar es Salaam',
                    'city' => 'Dar es Salaam',
                ]);
            }
        });
    }
}
