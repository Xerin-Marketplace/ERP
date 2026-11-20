<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\BranchAdministrator;

/**
 * DEMO/SAMPLE ACCOUNTS — for development, staging and training ONLY.
 * Every account shares the demo password shown in docs/administration.
 * Do NOT run on a real production dataset without rotating passwords.
 *
 * Idempotent: updateOrCreate on email; re-running only refreshes
 * password/branch/role — it never duplicates users.
 */
class SampleUsersSeeder extends Seeder
{
    public const PASSWORD = 'XerinERP@2026';

    public function run(): void
    {
        $b = fn (string $code) => Branch::where('code', $code)->value('id')
            ?? Branch::orderBy('id')->value('id');
        $dxb = $b('DXB'); $hq = $b('HQ'); $tz = $b('TZ');

        $users = [
            // ── GOVERNANCE ──────────────────────────────────────────
            ['email' => 'superadmin@xerinhub.com',     'name' => 'Super Admin',        'role' => 'super-admin',            'branch' => $hq],
            ['email' => 'admin@xerinhub.com',          'name' => 'Group Admin',        'role' => 'admin',                 'branch' => $hq],
            ['email' => 'auditor@xerinhub.com',        'name' => 'System Auditor',     'role' => 'system-auditor',        'branch' => $hq],

            // ── BRANCH ADMINISTRATORS ───────────────────────────────
            ['email' => 'admin.dubai@xerinhub.com',    'name' => 'Dubai Branch Admin', 'role' => 'branch-admin', 'branch' => $dxb, 'delegated' => $dxb],
            ['email' => 'admin.tanzania@xerinhub.com', 'name' => 'Tanzania Branch Admin','role' => 'branch-admin','branch' => $tz,  'delegated' => $tz],

            // ── EMPLOYEES / OPERATORS ───────────────────────────────
            ['email' => 'reception@xerinhub.com',      'name' => 'Receptionist',       'role' => 'receptionist',          'branch' => $hq],
            ['email' => 'hr@xerinhub.com',             'name' => 'HR Administrator',   'role' => 'hr-administrator',      'branch' => $hq],
            ['email' => 'hr.officer@xerinhub.com',     'name' => 'HR Officer',         'role' => 'hr-officer',            'branch' => $tz],
            ['email' => 'dept.manager@xerinhub.com',   'name' => 'Department Manager', 'role' => 'department-manager',    'branch' => $hq],
            ['email' => 'dept.admin@xerinhub.com',     'name' => 'Department Admin',   'role' => 'department-admin',      'branch' => $tz],
            ['email' => 'it.admin@xerinhub.com',       'name' => 'IT Administrator',   'role' => 'it-administrator',      'branch' => $hq],

            // ── CARGO / WAREHOUSE (Dubai) ───────────────────────────
            ['email' => 'warehouse@xerinhub.com',      'name' => 'Warehouse Officer',  'role' => 'Warehouse Officer',     'branch' => $dxb],
            ['email' => 'wh.manager@xerinhub.com',     'name' => 'Warehouse Manager',  'role' => 'Warehouse Manager',     'branch' => $dxb],
            ['email' => 'cargo@xerinhub.com',          'name' => 'Cargo Receiving Officer','role' => 'Cargo Receiving Officer','branch' => $dxb],
            ['email' => 'inspector@xerinhub.com',      'name' => 'Cargo Inspector',    'role' => 'Cargo Inspector',       'branch' => $dxb],

            // ── SHIPMENT / ARRIVAL / DELIVERY ───────────────────────
            ['email' => 'shipment@xerinhub.com',       'name' => 'Shipment Officer',   'role' => 'Shipment Officer',      'branch' => $dxb],
            ['email' => 'shipment.sup@xerinhub.com',   'name' => 'Shipment Supervisor','role' => 'Shipment Supervisor',   'branch' => $dxb],
            ['email' => 'arrival@xerinhub.com',        'name' => 'Arrival Officer',    'role' => 'Arrival Officer',       'branch' => $tz],

            // ── COMMERCIAL / FINANCE ────────────────────────────────
            ['email' => 'sales@xerinhub.com',          'name' => 'Sales Officer',      'role' => 'Sales Officer',         'branch' => $hq],
            ['email' => 'sales.manager@xerinhub.com',  'name' => 'Sales Manager',      'role' => 'Sales Manager',         'branch' => $hq],
            ['email' => 'finance@xerinhub.com',        'name' => 'Accountant',         'role' => 'accountant',            'branch' => $hq],
            ['email' => 'cashier@xerinhub.com',        'name' => 'Cashier',            'role' => 'cashier',               'branch' => $tz],
        ];

        foreach ($users as $u) {
            $user = User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make(self::PASSWORD),
                    'email_verified_at' => now(),
                    'status' => AccountStatus::Active,
                    'branch_id' => $u['branch'],
                    'must_change_password' => false,
                ]
            );
            // Additive — never strips an existing elevated role
            // (e.g. admin@xerinhub.com keeps super-admin in production).
            if (! $user->hasRole($u['role'])) {
                $user->assignRole($u['role']);
            }

            // Branch Admin = role + ACTIVE delegation row (authority source)
            if (isset($u['delegated'])) {
                BranchAdministrator::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'branch_id' => $u['delegated'],
                        'assigned_by' => User::where('email', 'superadmin@xerinhub.com')->value('id'),
                        'delegable_roles' => [
                            'employee', 'receptionist', 'hr-officer', 'department-manager',
                            'Cargo Receiving Officer', 'Warehouse Officer', 'Shipment Officer',
                            'Arrival Officer', 'Sales Officer', 'cashier',
                        ],
                        'delegable_permissions' => [
                            'users.view', 'users.create', 'users.update', 'users.suspend',
                            'users.assignments.view', 'users.assignments.manage',
                            'employee.view', 'employee.create', 'employee.update',
                            'cargo.view', 'shipment.view', 'arrival.view',
                        ],
                        'status' => 'active',
                        'revoked_at' => null, 'revoked_by' => null,
                    ]
                );
            }
        }
    }
}
