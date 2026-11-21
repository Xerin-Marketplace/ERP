<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Spec §16.1 — consolidate the role roster to the 15 specification seats
 * plus the platform-internal roles the machinery depends on
 * (super-admin, admin, branch-admin, employee).
 *
 * Renames preserve user assignments (role id unchanged). Overlapping
 * roles merge their permissions into the surviving spec role, then are
 * deleted. Stored approver_role strings are rewritten to spec names.
 */
return new class extends Migration
{
    private const RENAME = [
        'Cargo Receiving Officer'  => 'Dubai Receiving',
        'Cargo Operations'         => 'Dubai Warehouse and Cargo Operations',
        'Arrival Officer'          => 'Tanzania Receiving',
        'Customs Officer'          => 'Clearance',
        'Distribution Officer'     => 'Distribution Centre Officer',
        'finance_manager'          => 'Finance and Accounting',
        'Delivery Driver'          => 'Delivery',
        'receptionist'             => 'Customer Care',
        'Sales Officer'            => 'Sales and CRM',
        'ceo'                      => 'CEO and Management',
        'it-administrator'         => 'CTO and Admin',
        'system-auditor'           => 'Auditor',
        'branch-manager'           => 'Branch Manager',
        'Customer Service Officer' => 'Customer Care',
    ];

    /** spec role => roles whose permissions merge into it before deletion */
    private const MERGE = [
        'Dubai Warehouse and Cargo Operations' => [
            'Warehouse Manager', 'Warehouse Officer', 'Cargo Inspector',
            'Shipment Officer', 'Shipment Supervisor', 'Airline Coordinator',
        ],
        'Finance and Accounting' => [
            'accountant', 'cashier', 'finance_read_only',
            'group_finance_director', 'payroll-officer', 'payroll-approver', 'Finance Reviewer',
        ],
        'Delivery' => ['Dispatch Officer', 'Delivery Manager'],
        'Sales and CRM' => ['Sales Manager', 'tender-manager', 'tender-officer', 'quantity-surveyor'],
        'Tanzania Receiving' => ['TZ Operations Manager'],
        'Customer Care' => ['Claims Investigator', 'Claims Approver'],
        'Branch Manager' => ['department-manager'],
        'CTO and Admin' => ['it-officer', 'hr-officer', 'hr-administrator'],
        'CEO and Management' => ['group-administrator', 'company-administrator', 'management-viewer', 'compliance-officer', 'manager'],
    ];

    private const KEEP = [
        'Customer', 'Sales and CRM', 'Dubai Receiving',
        'Dubai Warehouse and Cargo Operations', 'Tanzania Receiving', 'Clearance',
        'Distribution Centre Officer', 'Finance and Accounting', 'Release Approver',
        'Delivery', 'Customer Care', 'Branch Manager', 'CEO and Management',
        'CTO and Admin', 'Auditor',
        'super-admin', 'admin', 'branch-admin', 'employee',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::RENAME as $old => $new) {
            $role = Role::where('name', $old)->first();
            if (! $role) continue;
            if (! Role::where('name', $new)->where('id', '!=', $role->id)->exists()) {
                $role->name = $new;
                $role->save();
                continue;
            }
            // Target already exists as a different row — treat as a merge.
            $target = Role::where('name', $new)->first();
            $target->syncPermissions($target->permissions->pluck('name')
                ->merge($role->permissions->pluck('name'))->unique()->values());
            DB::table('model_has_roles')->where('role_id', $role->id)
                ->get()->each(fn ($m) => DB::table('model_has_roles')->updateOrInsert(
                    ['role_id' => $target->id, 'model_type' => $m->model_type, 'model_id' => $m->model_id], []));
            $role->delete();
        }

        foreach (self::MERGE as $target => $sources) {
            $t = Role::where('name', $target)->first() ?? Role::create(['name' => $target, 'guard_name' => 'web']);
            $perms = $t->permissions->pluck('name');
            foreach ($sources as $name) {
                $r = Role::where('name', $name)->first();
                if (! $r) continue;
                $perms = $perms->merge($r->permissions->pluck('name'));
                // move any users holding the merged role onto the spec role
                DB::table('model_has_roles')->where('role_id', $r->id)
                    ->get()->each(fn ($m) => DB::table('model_has_roles')->updateOrInsert(
                        ['role_id' => $t->id, 'model_type' => $m->model_type, 'model_id' => $m->model_id], []));
                $r->delete();
            }
            $t->syncPermissions($perms->unique()->values());
        }

        Role::firstOrCreate(['name' => 'Customer', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Clearance', 'guard_name' => 'web']);

        // Delete obsolete roles plus their pivot rows (users on deleted roles
        // get no replacement — every named user was remapped above).
        $dead = Role::whereNotIn('name', self::KEEP)->get();
        DB::table('model_has_roles')->whereIn('role_id', $dead->pluck('id'))->delete();
        DB::table('role_has_permissions')->whereIn('role_id', $dead->pluck('id'))->delete();
        $dead->each->delete();

        // Rewrite stored approver_role references in live approval chains.
        $roleFix = [
            'finance_manager' => 'Finance and Accounting', 'finance_director' => 'Finance and Accounting',
            'accountant' => 'Finance and Accounting', 'cashier' => 'Finance and Accounting',
            'group_finance_director' => 'Finance and Accounting',
            'branch-manager' => 'Branch Manager', 'branch_manager' => 'Branch Manager',
            'it-administrator' => 'CTO and Admin', 'it-officer' => 'CTO and Admin',
            'hr-administrator' => 'CTO and Admin', 'hr-officer' => 'CTO and Admin', 'hr_officer' => 'CTO and Admin',
            'hr_manager' => 'CTO and Admin',
            'department-manager' => 'Branch Manager', 'receptionist' => 'Customer Care',
            'finance' => 'Finance and Accounting', 'executive' => 'CEO and Management',
        ];
        foreach (['finance_approvals', 'hr_approvals', 'ticket_approvals', 'access_approval_tasks'] as $table) {
            if (! \Schema::hasTable($table) || ! \Schema::hasColumn($table, 'approver_role')) continue;
            foreach ($roleFix as $from => $to) {
                DB::table($table)->where('approver_role', $from)->update(['approver_role' => $to]);
            }
            // 'manager' means "requester's line manager" in ticketing only —
            // everywhere else it is a role label for Branch Manager.
            if ($table !== 'ticket_approvals') {
                DB::table($table)->where('approver_role', 'manager')->update(['approver_role' => 'Branch Manager']);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Intentionally irreversible — role consolidation is a data migration.
    }
};
