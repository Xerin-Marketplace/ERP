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
    ];

    public const ROLES = [
        'hr-administrator' => self::PERMISSIONS,
        'hr-officer' => [
            'hr.dashboard.view', 'hr.employee.view',
            'attendance.view', 'attendance.correct', 'attendance.approve', 'attendance.reports.view',
            'leave.view', 'leave.approve', 'overtime.approve',
            'shift.view', 'shift.create', 'shift.assign',
            'recruitment.view', 'recruitment.manage',
            'performance.view', 'training.view', 'training.manage',
            'welfare.view', 'welfare.manage',
        ],
        'manager' => [
            'attendance.view', 'leave.view', 'leave.approve',
            'overtime.approve', 'shift.view', 'performance.view', 'performance.assess',
            'training.view',
        ],
        'employee' => [
            'attendance.clock_in', 'attendance.clock_out',
            'leave.request', 'leave.view',
            'overtime.request', 'shift.view',
            'performance.view', 'training.view', 'welfare.view',
        ],
        'it-administrator' => self::PERMISSIONS,
        'super-admin' => self::PERMISSIONS,
        'group-administrator' => self::PERMISSIONS,
        'company-administrator' => [
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
            ['code' => 'AL',  'name' => 'Annual Leave',        'annual_days' => 28, 'carry_forward_max' => 10, 'max_consecutive_days' => 28, 'approval_chain' => ['manager']],
            ['code' => 'SL',  'name' => 'Sick Leave',          'annual_days' => 126, 'requires_document' => true, 'approval_chain' => ['manager', 'hr_officer']],
            ['code' => 'ML',  'name' => 'Maternity Leave',     'annual_days' => 84, 'eligibility' => ['gender' => 'female'], 'requires_document' => true, 'approval_chain' => ['manager', 'hr_officer']],
            ['code' => 'PL',  'name' => 'Paternity Leave',     'annual_days' => 3, 'eligibility' => ['gender' => 'male'], 'requires_document' => true, 'approval_chain' => ['manager']],
            ['code' => 'CL',  'name' => 'Compassionate Leave', 'annual_days' => 7, 'max_consecutive_days' => 7, 'approval_chain' => ['manager']],
            ['code' => 'STL', 'name' => 'Study Leave',         'annual_days' => 15, 'requires_document' => true, 'approval_chain' => ['manager', 'hr_officer']],
            ['code' => 'UPL', 'name' => 'Unpaid Leave',        'annual_days' => 30, 'paid' => false, 'approval_chain' => ['manager', 'hr_manager']],
            ['code' => 'SPL', 'name' => 'Special Leave',       'annual_days' => 5, 'approval_chain' => ['manager', 'hr_officer']],
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
