<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\JobPosition;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * XERIN STAFF ROSTER — the real operating team from the master spec §16.2,
 * placed into the departments already seeded per branch ({DEF}-{BRANCH}).
 *
 * Idempotent: keyed on user email / employee_number / position code.
 * Shared dev password documented in docs — rotate on production.
 */
class XerinStaffSeeder extends Seeder
{
    public const PASSWORD = 'XerinERP@2026';

    /** Spec §16.2 roster. */
    private const STAFF = [
        // ── DUBAI — Receiving & Cargo Operations ─────────────────────
        ['name' => 'Ezekiel',  'email' => 'ezekiel@xerinhub.com',  'branch' => 'DXB', 'dept' => 'WH',
         'position' => ['code' => 'DXB-CARGO-LEAD', 'title' => 'Cargo Operations Lead'],
         'roles' => ['Cargo Receiving Officer', 'Cargo Operations', 'Warehouse Manager'], 'head' => true],
        ['name' => 'Faisal',   'email' => 'faisal@xerinhub.com',   'branch' => 'DXB', 'dept' => 'WH',
         'position' => ['code' => 'DXB-RCV-OFF', 'title' => 'Dubai Receiving Officer'],
         'roles' => ['Cargo Receiving Officer', 'Cargo Operations']],
        ['name' => 'Amina',    'email' => 'amina@xerinhub.com',    'branch' => 'DXB', 'dept' => 'WH',
         'position' => ['code' => 'DXB-RCV-OFF', 'title' => 'Dubai Receiving Officer'],
         'roles' => ['Cargo Receiving Officer', 'Cargo Operations']],
        ['name' => 'Conrad',   'email' => 'conrad@xerinhub.com',   'branch' => 'DXB', 'dept' => 'WH',
         'position' => ['code' => 'DXB-RCV-OFF', 'title' => 'Dubai Receiving Officer'],
         'roles' => ['Cargo Receiving Officer', 'Cargo Operations']],

        // ── TANZANIA — Receiving ─────────────────────────────────────
        ['name' => 'Faith',    'email' => 'faith@xerinhub.com',    'branch' => 'TZ', 'dept' => 'WH',
         'position' => ['code' => 'TZ-RCV-OFF', 'title' => 'Tanzania Receiving Officer'],
         'roles' => ['Arrival Officer']],
        ['name' => 'Abdul',    'email' => 'abdul@xerinhub.com',    'branch' => 'TZ', 'dept' => 'WH',
         'position' => ['code' => 'TZ-RCV-OFF', 'title' => 'Tanzania Receiving Officer'],
         'roles' => ['Arrival Officer']],
        ['name' => 'Wahida',   'email' => 'wahida@xerinhub.com',   'branch' => 'TZ', 'dept' => 'WH',
         'position' => ['code' => 'TZ-RCV-OFF', 'title' => 'Tanzania Receiving Officer'],
         'roles' => ['Arrival Officer']],

        // ── TANZANIA — Distribution Centre ───────────────────────────
        ['name' => 'Hashim',   'email' => 'hashim@xerinhub.com',   'branch' => 'TZ', 'dept' => 'OPS',
         'position' => ['code' => 'TZ-DC-OFF', 'title' => 'Distribution Centre Officer'],
         'roles' => ['Distribution Officer'], 'head' => true],

        // ── TANZANIA — Finance / Release ─────────────────────────────
        ['name' => 'Sabrina',  'email' => 'sabrina@xerinhub.com',  'branch' => 'TZ', 'dept' => 'FIN',
         'position' => ['code' => 'TZ-FIN-MGR', 'title' => 'Finance & Accounting Manager'],
         'roles' => ['finance_manager', 'accountant'], 'head' => true],
        ['name' => 'Jacqueline','email' => 'jacqueline@xerinhub.com','branch' => 'TZ', 'dept' => 'OPS',
         'position' => ['code' => 'TZ-REL-APP', 'title' => 'Release Approver'],
         'roles' => ['Release Approver']],

        // ── TANZANIA — Delivery ──────────────────────────────────────
        ['name' => 'Ubaya',    'email' => 'ubaya@xerinhub.com',    'branch' => 'TZ', 'dept' => 'FLT',
         'position' => ['code' => 'TZ-DRIVER', 'title' => 'Delivery Driver'],
         'roles' => ['Delivery Driver']],

        // ── GROUP — Leadership / Admin ───────────────────────────────
        ['name' => 'Joseph',   'email' => 'joseph@xerinhub.com',   'branch' => 'HQ', 'dept' => 'IT',
         'position' => ['code' => 'HQ-CTO', 'title' => 'Chief Technology Officer'],
         'roles' => ['it-administrator', 'admin'], 'head' => true],
        ['name' => 'Chief Executive','email' => 'ceo@xerinhub.com','branch' => 'HQ', 'dept' => null,
         'position' => ['code' => 'HQ-CEO', 'title' => 'Chief Executive Officer'],
         'roles' => ['ceo']],

        // ── Branch managers (functional seats, per spec matrix) ──────
        ['name' => 'Dubai Branch Manager',    'email' => 'manager.dxb@xerinhub.com', 'branch' => 'DXB', 'dept' => 'OPS',
         'position' => ['code' => 'DXB-BM', 'title' => 'Branch Manager — Dubai'],
         'roles' => ['branch-manager'], 'head' => true],
        ['name' => 'Tanzania Branch Manager', 'email' => 'manager.tz@xerinhub.com',  'branch' => 'TZ', 'dept' => 'OPS',
         'position' => ['code' => 'TZ-BM', 'title' => 'Branch Manager — Tanzania'],
         'roles' => ['branch-manager']],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // CEO — group-wide view of every module, no mutations (spec §16.3).
        Role::firstOrCreate(['name' => 'ceo', 'guard_name' => 'web'])
            ->syncPermissions(Permission::where('name', 'like', '%.view')->pluck('name'));

        $company = Company::where('code', 'XG')->firstOrFail();
        $seq = (int) (Employee::max('id') ?? 0) + 1;

        foreach (self::STAFF as $s) {
            $branch = Branch::where('code', $s['branch'])->first();
            if (! $branch) continue;

            $user = User::updateOrCreate(
                ['email' => $s['email']],
                [
                    'name' => $s['name'],
                    'password' => Hash::make(self::PASSWORD),
                    'email_verified_at' => now(),
                    'status' => AccountStatus::Active,
                    'branch_id' => $branch->id,
                    'must_change_password' => true,
                ]
            );
            foreach ($s['roles'] as $role) {
                if (! $user->hasRole($role)) $user->assignRole($role);
            }

            $dept = $s['dept']
                ? Department::where('code', "{$s['dept']}-{$branch->code}")->first()
                : null;
            $position = JobPosition::firstOrCreate(
                ['code' => $s['position']['code']],
                [
                    'title' => $s['position']['title'],
                    'company_id' => $company->id,
                    'department_id' => $dept?->id,
                    'status' => 'active',
                ]
            );

            $employee = Employee::where('user_id', $user->id)->first()
                ?? new Employee(['user_id' => $user->id]);
            $employee->fill([
                'status' => 'active',
                'employment_type' => 'permanent',
                'start_date' => $employee->start_date ?? now()->toDateString(),
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'department_id' => $dept?->id,
                'job_position_id' => $position->id,
                'official_email' => $s['email'],
            ]);
            if (! $employee->employee_number) {
                $employee->forceFill(['employee_number' => sprintf('XG-EMP-%04d', $seq++)]);
            }
            $employee->save();

            if (($s['head'] ?? false) && $dept) {
                $dept->update(['head_id' => $employee->id]);
            }
        }
    }
}
