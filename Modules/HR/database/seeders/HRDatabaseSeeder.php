<?php

namespace Modules\HR\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HR\Models\LeaveType;
use Modules\HR\Models\ShiftDefinition;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class HRDatabaseSeeder extends Seeder
{
    public const PERMISSIONS = [
        'hr.dashboard.view', 'hr.employee.view', 'hr.employee.manage',
        'attendance.view', 'attendance.clock_in', 'attendance.clock_out',
        'attendance.correct', 'attendance.approve', 'attendance.reports.view',
        'leave.view', 'leave.request', 'leave.approve', 'leave.manage', 'leave.policy.manage',
        'shift.view', 'shift.create', 'shift.assign', 'shift.manage',
        'overtime.request', 'overtime.approve', 'overtime.manage',
        'recruitment.view', 'recruitment.manage', 'recruitment.approve',
        'performance.view', 'performance.assess', 'performance.manage',
        'training.view', 'training.manage',
        'disciplinary.view', 'disciplinary.manage',
        'welfare.view', 'welfare.manage',
        'hr.bank_details.view', 'hr.bank_details.manage', 'hr.bank_details.verify',
        'hr.compensation.view', 'hr.compensation.manage', 'hr.compensation.approve',
        'hr.payroll.view', 'hr.payroll.process', 'hr.payroll.approve', 'hr.payroll.export',
    ];

    public const ROLES = [
        // Spec §16: HR administration falls to CTO and Admin; branch-level
        // people management to Branch Manager; payroll to Finance.
        'CTO and Admin' => self::PERMISSIONS,
        'super-admin' => self::PERMISSIONS,
        'Branch Manager' => [
            'hr.dashboard.view', 'hr.employee.view', 'hr.employee.manage',
            'attendance.view', 'attendance.approve', 'attendance.correct', 'attendance.reports.view',
            'leave.view', 'leave.approve', 'leave.manage',
            'shift.view', 'shift.assign', 'shift.manage',
            'overtime.approve', 'overtime.manage',
            'recruitment.view', 'recruitment.manage',
            'performance.view', 'performance.assess',
            'training.view', 'training.manage', 'welfare.view', 'welfare.manage',
            'disciplinary.view',
        ],
        'Finance and Accounting' => [
            'hr.dashboard.view', 'hr.employee.view',
            'hr.bank_details.view', 'hr.bank_details.manage', 'hr.bank_details.verify',
            'hr.compensation.view', 'hr.compensation.manage', 'hr.compensation.approve',
            'hr.payroll.view', 'hr.payroll.process', 'hr.payroll.approve', 'hr.payroll.export',
        ],
        'Auditor' => [
            'hr.dashboard.view', 'hr.employee.view',
            'attendance.view', 'attendance.reports.view',
            'leave.view', 'shift.view', 'recruitment.view',
            'performance.view', 'training.view', 'welfare.view', 'disciplinary.view',
            'hr.bank_details.view', 'hr.compensation.view', 'hr.payroll.view',
        ],
        'employee' => [
            'attendance.clock_in', 'attendance.clock_out',
            'leave.request', 'leave.view',
            'overtime.request', 'shift.view',
            'performance.view', 'training.view', 'welfare.view',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        foreach (self::ROLES as $role => $perms) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'])
                ->syncPermissions(array_unique(array_merge($perms, [])));
        }

        // Tanzanian-aligned leave types (configurable — not hardcoded in code)
        $leaveTypes = [
            ['code' => 'AL',  'name' => 'Annual Leave',        'annual_days' => 28, 'carry_forward_max' => 10, 'max_consecutive_days' => 28, 'approval_chain' => ['Branch Manager']],
            ['code' => 'SL',  'name' => 'Sick Leave',          'annual_days' => 126, 'requires_document' => true, 'approval_chain' => ['Branch Manager', 'CTO and Admin']],
            ['code' => 'ML',  'name' => 'Maternity Leave',     'annual_days' => 84, 'eligibility' => ['gender' => 'female'], 'requires_document' => true, 'approval_chain' => ['Branch Manager', 'CTO and Admin']],
            ['code' => 'PL',  'name' => 'Paternity Leave',     'annual_days' => 3, 'eligibility' => ['gender' => 'male'], 'requires_document' => true, 'approval_chain' => ['Branch Manager']],
            ['code' => 'CL',  'name' => 'Compassionate Leave', 'annual_days' => 7, 'max_consecutive_days' => 7, 'approval_chain' => ['Branch Manager']],
            ['code' => 'STL', 'name' => 'Study Leave',         'annual_days' => 15, 'requires_document' => true, 'approval_chain' => ['Branch Manager', 'CTO and Admin']],
            ['code' => 'UPL', 'name' => 'Unpaid Leave',        'annual_days' => 30, 'paid' => false, 'approval_chain' => ['Branch Manager', 'CTO and Admin']],
            ['code' => 'SPL', 'name' => 'Special Leave',       'annual_days' => 5, 'approval_chain' => ['Branch Manager', 'CTO and Admin']],
        ];
        foreach ($leaveTypes as $lt) {
            LeaveType::firstOrCreate(['code' => $lt['code']], $lt);
        }

        // Default shifts
        $shifts = [
            ['code' => 'DAY',   'name' => 'Day Shift',   'start_time' => '08:00', 'end_time' => '17:00', 'break_minutes' => 60, 'grace_minutes' => 15, 'working_days' => [1,2,3,4,5]],
            ['code' => 'NIGHT', 'name' => 'Night Shift', 'start_time' => '18:00', 'end_time' => '06:00', 'overnight' => true, 'break_minutes' => 60, 'grace_minutes' => 15, 'working_days' => [1,2,3,4,5]],
            ['code' => 'FLEX',  'name' => 'Flexi Time',  'start_time' => '07:30', 'end_time' => '16:30', 'break_minutes' => 60, 'grace_minutes' => 30, 'working_days' => [1,2,3,4,5]],
            ['code' => 'SAT',   'name' => 'Saturday',    'start_time' => '09:00', 'end_time' => '13:00', 'break_minutes' => 0, 'grace_minutes' => 15, 'working_days' => [6]],
        ];
        foreach ($shifts as $s) {
            ShiftDefinition::firstOrCreate(['code' => $s['code']], $s);
        }
    }
}
