<?php

namespace Modules\HR\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Employee\Models\Employee;
use Modules\HR\Models\AttendanceRecord;
use Modules\HR\Models\JobVacancy;
use Modules\HR\Models\LeaveBalance;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Models\OvertimeRequest;
use Modules\HR\Models\RecruitmentApplication;
use Modules\HR\Models\ShiftAssignment;
use Modules\HR\Models\TrainingEnrollment;

class HRDashboardService
{
    public function for(User $viewer): array
    {
        $own = $viewer->employee;
        $companyScope = $viewer->can('hr.dashboard.view') ? null : $own?->company_id;

        return [
            'employees' => [
                'total' => Employee::when($companyScope, fn ($q) => $q->where('company_id', $companyScope))->count(),
                'active' => Employee::where('status', 'active')->when($companyScope, fn ($q) => $q->where('company_id', $companyScope))->count(),
                'probation' => Employee::where('status', 'probation')->when($companyScope, fn ($q) => $q->where('company_id', $companyScope))->count(),
            ],
            'attendance_today' => $this->attendanceToday($companyScope),
            'leave' => [
                'pending' => LeaveRequest::where('status', 'pending')->count(),
                'approved_this_month' => LeaveRequest::where('status', 'approved')->whereMonth('decided_at', now()->month)->count(),
            ],
            'overtime_pending' => OvertimeRequest::where('status', 'pending')->count(),
            'recruitment' => [
                'open_vacancies' => JobVacancy::where('status', 'published')->count(),
                'applications' => RecruitmentApplication::count(),
                'interviews_pending' => RecruitmentApplication::where('stage', 'interview')->count(),
            ],
            'training_active' => TrainingEnrollment::whereIn('status', ['enrolled', 'attended'])->count(),
        ];
    }

    private function attendanceToday(?int $companyId): array
    {
        $rows = AttendanceRecord::whereDate('work_date', now()->toDateString())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return [
            'present' => ($rows['present'] ?? 0) + ($rows['late'] ?? 0),
            'late' => $rows['late'] ?? 0,
            'absent' => $rows['absent'] ?? 0,
            'on_leave' => $rows['on_leave'] ?? 0,
        ];
    }

    /** Employee self-service dashboard. */
    public function selfService(Employee $employee): array
    {
        $year = (int) now()->format('Y');
        app(LeaveService::class)->ensureBalances($employee, $year);

        return [
            'leave_balances' => LeaveBalance::with('type')->where('employee_id', $employee->id)->where('year', $year)->get(),
            'attendance_month' => app(AttendanceService::class)->monthly($year, (int) now()->format('n'), $employee),
            'pending_leave' => LeaveRequest::where('employee_id', $employee->id)->where('status', 'pending')->count(),
            'pending_overtime' => OvertimeRequest::where('employee_id', $employee->id)->where('status', 'pending')->count(),
            'shift' => ShiftAssignment::with('shift')->where('employee_id', $employee->id)->where('status', 'active')->latest('effective_from')->first()?->shift,
        ];
    }

    /** HR reporting aggregates for the reports screen/API. */
    public function report(?Carbon $from, ?Carbon $to, ?int $companyId): array
    {
        $from ??= now()->startOfMonth();
        $to ??= now();

        $records = AttendanceRecord::whereBetween('work_date', [$from->startOfDay(), $to->endOfDay()])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        $leave = LeaveRequest::whereBetween('start_date', [$from->toDateString(), $to->toDateString()]);

        return [
            'attendance' => [
                'records' => (clone $records)->count(),
                'late' => (clone $records)->where('status', 'late')->count(),
                'absent' => (clone $records)->where('status', 'absent')->count(),
                'avg_late_minutes' => round((float) (clone $records)->avg('late_minutes'), 1),
                'total_worked_hours' => round(((clone $records)->sum('worked_minutes')) / 60, 1),
                'total_overtime_hours' => round(((clone $records)->sum('overtime_minutes')) / 60, 1),
            ],
            'leave' => [
                'requested' => (clone $leave)->count(),
                'approved' => (clone $leave)->where('status', 'approved')->count(),
                'rejected' => (clone $leave)->where('status', 'rejected')->count(),
                'days_approved' => (clone $leave)->where('status', 'approved')->sum('days'),
            ],
            'overtime' => [
                'requests' => OvertimeRequest::whereBetween('work_date', [$from->startOfDay(), $to->endOfDay()])->count(),
                'approved_hours' => OvertimeRequest::where('status', 'approved')->sum('actual_hours') ?: 0,
            ],
            'recruitment' => [
                'applications' => RecruitmentApplication::whereBetween('created_at', [$from, $to])->count(),
                'hired' => RecruitmentApplication::where('stage', 'hired')->whereBetween('updated_at', [$from, $to])->count(),
            ],
        ];
    }
}
