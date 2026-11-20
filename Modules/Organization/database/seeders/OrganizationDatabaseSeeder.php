<?php

namespace Modules\Organization\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;
use Modules\Organization\Models\DepartmentDefinition;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class OrganizationDatabaseSeeder extends Seeder
{
    /**
     * Legacy permission names — kept in the database for backward compatibility
     * with grants issued before the `organization.*` naming convention was
     * adopted. Routes no longer reference these; do not add new usages.
     */
    public const LEGACY_PERMISSIONS = [
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

    /** Canonical permission set (see docs/phase-1/PHASE_1_RBAC_MATRIX.md). */
    public const PERMISSIONS = [
        'organization.dashboard.view',
        'organization.structure.view',
        'organization.audit.view',
        'organization.settings.manage',
        'organization.company.view',
        'organization.company.create',
        'organization.company.update',
        'organization.company.activate',
        'organization.company.suspend',
        'organization.company.archive',
        'organization.branches.view',
        'organization.branches.create',
        'organization.branches.update',
        'organization.branches.activate',
        'organization.branches.deactivate',
        'organization.branches.manage',
        'organization.locations.view',
        'organization.locations.create',
        'organization.locations.update',
        'organization.locations.delete',
        'organization.departments.view',
        'organization.departments.create',
        'organization.departments.update',
        'organization.departments.deactivate',
        'organization.departments.manage',
        'organization.positions.view',
        'organization.positions.create',
        'organization.positions.update',
        'organization.positions.manage',
        'organization.branch_administrators.view',
        'organization.branch_administrators.assign',
        'organization.branch_administrators.revoke',
    ];

    /** Everything a Group Admin may do inside the organization domain. */
    public const GROUP_ADMIN_PERMISSIONS = self::PERMISSIONS;

    /** Branch-scoped view + permitted branch-level resources (no branch management). */
    public const BRANCH_ADMIN_PERMISSIONS = [
        'organization.dashboard.view',
        'organization.branches.view',
        'organization.departments.view',
        'organization.departments.create',
        'organization.departments.update',
        'organization.departments.deactivate',
        'organization.positions.view',
    ];

    /** Default branches required by the multi-branch architecture. */
    public const DEFAULT_BRANCHES = [
        [
            'code' => 'DXB',
            'name' => 'Dubai Branch',
            'branch_type' => 'regional_office',
            'country' => 'United Arab Emirates',
            'country_code' => 'AE',
            'city' => 'Dubai',
            'region' => 'Dubai',
            'currency' => 'AED',
            'timezone' => 'Asia/Dubai',
        ],
        [
            'code' => 'TZ',
            'name' => 'Tanzania Branch',
            'branch_type' => 'head_office',
            'country' => 'Tanzania',
            'country_code' => 'TZ',
            'city' => 'Dar es Salaam',
            'region' => 'Dar es Salaam',
            'currency' => 'TZS',
            'timezone' => 'Africa/Dar_es_Salaam',
        ],
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
        ['code' => 'WH', 'name' => 'Warehouse Operations'],
        ['code' => 'CUS', 'name' => 'Customs & Clearing'],
        ['code' => 'FLT', 'name' => 'Fleet & Delivery'],
        ['code' => 'QA', 'name' => 'Quality & Safety'],
    ];

    /** Departments seeded into every branch: [definition_code, name, description] */
    public const DEFAULT_DEPARTMENTS = [
        ['OPS',   'Operations',           'Branch operations coordination and service execution'],
        ['WH',    'Warehouse Operations', 'Receiving, storage, picking, packing and dispatch'],
        ['CUS',   'Customs & Clearing',   'Import/export documentation and customs clearance'],
        ['FLT',   'Fleet & Delivery',     'Drivers, vehicles, last-mile delivery and returns'],
        ['SALES', 'Sales & Commercial',   'Sales pipeline, quotations, contracts and agents'],
        ['CS',    'Customer Service',     'Customer support, complaints and claims handling'],
        ['FIN',   'Finance & Accounting', 'Invoicing, payments, accounts and reporting'],
        ['PROC',  'Procurement',          'Supplier management and purchasing'],
        ['HR',    'HR & Administration',  'People, payroll and office administration'],
        ['IT',    'Information Technology','Systems, infrastructure and user support'],
        ['QA',    'Quality & Safety',     'Quality assurance, HSE and compliance audits'],
        ['LEGAL', 'Legal & Compliance',   'Contracts, regulatory and company secretarial'],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            /* Permissions — idempotent (legacy names kept for existing grants) */
            foreach (array_merge(self::LEGACY_PERMISSIONS, self::PERMISSIONS) as $name) {
                Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            }
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $grantMap = [
                'super-admin' => array_merge(self::LEGACY_PERMISSIONS, self::PERMISSIONS),
                'group-administrator' => self::GROUP_ADMIN_PERMISSIONS,
                'company-administrator' => self::GROUP_ADMIN_PERMISSIONS,
                'branch-manager' => self::BRANCH_ADMIN_PERMISSIONS,
                'department-manager' => [
                    'organization.dashboard.view', 'organization.branches.view',
                    'organization.departments.view', 'organization.positions.view',
                ],
                'admin' => [
                    'organization.dashboard.view', 'organization.structure.view',
                    'organization.company.view', 'organization.branches.view',
                    'organization.locations.view', 'organization.departments.view',
                    'organization.positions.view',
                ],
                'manager' => [
                    'organization.dashboard.view', 'organization.structure.view',
                    'organization.company.view', 'organization.branches.view',
                    'organization.departments.view', 'organization.positions.view',
                ],
            ];

            foreach ($grantMap as $role => $permissions) {
                if ($r = Role::where('name', $role)->first()) {
                    $r->givePermissionTo($permissions);
                }
            }

            /* Department definitions — reusable classifications */
            foreach (self::DEFINITIONS as $def) {
                DepartmentDefinition::firstOrCreate(['code' => $def['code']], $def);
            }

            /* Root company — the group itself */
            $group = Company::firstOrCreate(
                ['code' => 'XG'],
                [
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
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
                    'country_code' => 'TZ',
                    'currency' => 'TZS',
                    'timezone' => 'Africa/Dar_es_Salaam',
                    'region' => 'Dar es Salaam',
                    'city' => 'Dar es Salaam',
                ]);
            }

            /* Default branches — idempotent on stable global codes */
            foreach (self::DEFAULT_BRANCHES as $data) {
                $branch = Branch::firstOrNew(['code' => $data['code']]);
                if (! $branch->exists) {
                    $branch->fill($data + [
                        'company_id' => $group->id,
                        'status' => \Modules\Organization\Enums\EntityStatus::Active->value,
                        'activated_at' => now(),
                    ])->save();
                } else {
                    // Backfill operational fields only; never overwrite admin edits.
                    $branch->fill(array_filter([
                        'company_id' => $branch->company_id ?: $group->id,
                        'country_code' => $branch->country_code ?: ($data['country_code'] ?? null),
                        'currency' => $branch->currency ?: ($data['currency'] ?? null),
                        'timezone' => $branch->timezone ?: ($data['timezone'] ?? null),
                    ]))->save();
                }
            }
            /* Default departments per branch — idempotent on {DEF}-{BRANCH} codes */
            foreach (Branch::all() as $branch) {
                foreach (self::DEFAULT_DEPARTMENTS as [$defCode, $name, $desc]) {
                    Department::firstOrCreate(
                        ['code' => "{$defCode}-{$branch->code}"],
                        [
                            'department_definition_id' => DepartmentDefinition::where('code', $defCode)->value('id'),
                            'name' => $name,
                            'description' => $desc,
                            'company_id' => $branch->company_id ?: $group->id,
                            'branch_id' => $branch->id,
                            'status' => \Modules\Organization\Enums\EntityStatus::Active,
                            'effective_date' => now()->toDateString(),
                        ]
                    );
                }
            }

        });
    }
}
