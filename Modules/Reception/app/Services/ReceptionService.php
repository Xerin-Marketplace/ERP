<?php

namespace Modules\Reception\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Employee\Models\Employee;
use Modules\HR\Models\AttendanceRecord;
use Modules\Ticketing\Models\Ticket;

/**
 * Branch-scoped read service for the Receptionist workspace.
 * HR attendance and Ticketing remain the source of truth; this service only reads.
 */
class ReceptionService
{
    /**
     * Resolve the branch a receptionist operates in. Super-admins may pass ?branch_id=.
     */
    public function scopeBranch(User $user, ?int $requested = null): ?int
    {
        $emp = $user->employee;

        if ($user->hasRole('super-admin') || $user->can('employee.view_all')) {
            return $requested ?: $emp?->branch_id;
        }

        return $emp?->branch_id;
    }

    public function kpis(?int $branchId): array
    {
        $today = today()->toDateString();

        $employees = Employee::query()->where('status', '!=', 'terminated');
        if ($branchId) {
            $employees->where('branch_id', $branchId);
        }
        $totalEmployees = (clone $employees)->count();
        $employeeIds = (clone $employees)->pluck('id');

        $attendance = AttendanceRecord::whereIn('employee_id', $employeeIds)->whereDate('work_date', $today);

        $present = (clone $attendance)->whereIn('status', ['present', 'late', 'half_day'])->count();
        $late = (clone $attendance)->where(function ($q) {
            $q->where('status', 'late')->orWhere('late_minutes', '>', 0);
        })->count();
        $checkedOut = (clone $attendance)->whereNotNull('clock_out_at')->count();
        $confirmedAbsent = (clone $attendance)->where('status', 'absent')->count();
        // Employees with no record yet are "unrecorded", not automatically absent.
        $unrecorded = max(0, $totalEmployees - (clone $attendance)->count());

        $tickets = Ticket::query();
        if ($branchId) {
            $tickets->where('requester_branch_id', $branchId);
        }
        $openTickets = (clone $tickets)->whereNotIn('status', ['resolved', 'closed'])->count();
        $inProgress = (clone $tickets)->where('status', 'in_progress')->count();
        $resolvedToday = (clone $tickets)->whereDate('resolved_at', $today)->count();

        return compact('totalEmployees', 'present', 'late', 'checkedOut', 'confirmedAbsent', 'unrecorded', 'openTickets', 'inProgress', 'resolvedToday');
    }

    public function todayAttendance(?int $branchId, ?int $departmentId = null, ?string $date = null): Collection
    {
        $date ??= today()->toDateString();

        return AttendanceRecord::query()
            ->with(['employee:id,employee_number,department_id,branch_id', 'employee.personal:id,employee_id,first_name,last_name', 'employee.department:id,name'])
            ->whereDate('work_date', $date)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->orderBy('clock_in_at')
            ->get();
    }

    /**
     * Attendance trend for charts: counts per status over the last N days.
     */
    public function attendanceTrend(?int $branchId, int $days = 7): array
    {
        $from = today()->subDays($days - 1)->toDateString();

        $rows = AttendanceRecord::query()
            ->select('work_date', 'status', DB::raw('count(*) as total'))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('work_date', '>=', $from)
            ->groupBy('work_date', 'status')
            ->orderBy('work_date')
            ->get();

        $labels = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $labels[] = today()->subDays($i)->toDateString();
        }

        $buckets = [
            'present' => array_fill(0, $days, 0),
            'late' => array_fill(0, $days, 0),
            'absent' => array_fill(0, $days, 0),
        ];

        foreach ($rows as $row) {
            $i = array_search(Carbon::parse($row->work_date)->toDateString(), $labels, true);
            if ($i === false) {
                continue;
            }
            $key = match (true) {
                $row->status === 'late' => 'late',
                in_array($row->status, ['present', 'half_day', 'on_leave'], true) => 'present',
                $row->status === 'absent' => 'absent',
                default => 'present',
            };
            $buckets[$key][$i] += (int) $row->total;
        }

        return ['labels' => $labels, 'series' => $buckets];
    }

    public function departmentBreakdown(?int $branchId): Collection
    {
        return AttendanceRecord::query()
            ->select('department_id', 'status', DB::raw('count(*) as total'))
            ->with('department:id,name')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('work_date', today())
            ->groupBy('department_id', 'status')
            ->get()
            ->groupBy(fn ($r) => $r->department?->name ?? 'Unassigned');
    }

    public function branchTickets(?int $branchId, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Ticket::query()
            ->with(['category:id,name', 'assignedAgent:id,name', 'requester:id,name'])
            ->when($branchId, fn ($q) => $q->where('requester_branch_id', $branchId))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['priority'] ?? null, fn ($q, $p) => $q->where('priority', $p))
            ->when($filters['q'] ?? null, fn ($q, $s) => $q->where(function ($qq) use ($s) {
                $qq->where('ticket_number', 'like', "%{$s}%")->orWhere('subject', 'like', "%{$s}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * IT technicians (ticket team members) with availability + active workload.
     */
    public function technicians(?int $branchId): Collection
    {
        return DB::table('ticket_team_members')
            ->join('employees', 'employees.id', '=', 'ticket_team_members.employee_id')
            ->leftJoin('employee_personal_details as pd', 'pd.employee_id', '=', 'employees.id')
            ->join('ticket_teams', 'ticket_teams.id', '=', 'ticket_team_members.team_id')
            ->leftJoin('users', 'users.id', '=', 'employees.user_id')
            ->select(
                'employees.id as employee_id', 'pd.first_name', 'pd.last_name',
                'employees.branch_id', 'ticket_teams.name as team',
                'ticket_team_members.availability', 'ticket_team_members.availability_updated_at',
                'users.id as user_id'
            )
            ->when($branchId, fn ($q) => $q->where('employees.branch_id', $branchId))
            ->orderBy('pd.first_name')
            ->get()
            ->map(function ($row) {
                $row->active_tickets = $row->user_id
                    ? Ticket::where('assigned_agent_id', $row->user_id)->whereNotIn('status', ['resolved', 'closed'])->count()
                    : 0;

                return $row;
            });
    }
}
