<?php

namespace Modules\HR\Http\Controllers\Api\V1;

use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Models\AttendanceCorrection;
use Modules\HR\Models\JobVacancy;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Models\LeaveType;
use Modules\HR\Models\OvertimeRequest;
use Modules\HR\Models\ShiftDefinition;
use Modules\HR\Services\AttendanceService;
use Modules\HR\Services\HRDashboardService;
use Modules\HR\Services\LeaveService;
use Modules\HR\Services\OvertimeService;
use Modules\HR\Services\ShiftService;

class HRApiController extends Controller
{
    public function __construct(
        private AttendanceService $attendance,
        private LeaveService $leave,
        private ShiftService $shifts,
        private OvertimeService $overtime,
        private HRDashboardService $dash,
    ) {}

    public function dashboard(Request $request)
    {
        abort_unless($request->user()->can('hr.dashboard.view'), 403);
        return ApiResponse::success($this->dash->for($request->user()));
    }

    /* ---- Attendance ---- */
    public function attendance(Request $request)
    {
        $records = $this->attendance->query($request->user())
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->date, fn ($q, $d) => $q->where('work_date', $d))
            ->paginate(20);
        return ApiResponse::success($records);
    }

    public function clockIn(Request $request)
    {
        abort_unless($request->user()->can('attendance.clock_in'), 403);
        $record = $this->attendance->clockIn($this->employee($request), $request->user());
        return ApiResponse::success($record, 'Clocked in.', 201);
    }

    public function clockOut(Request $request)
    {
        abort_unless($request->user()->can('attendance.clock_out'), 403);
        $record = $this->attendance->clockOut($this->employee($request), $request->user());
        return ApiResponse::success($record, 'Clocked out.');
    }

    /* ---- Leave ---- */
    public function leave(Request $request)
    {
        return ApiResponse::success($this->leave->query($request->user())->paginate(15));
    }

    public function requestLeave(Request $request)
    {
        abort_unless($request->user()->can('leave.request'), 403);
        $data = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'half_day' => 'nullable|boolean',
            'reason' => 'nullable|string|max:2000',
        ]);
        $req = $this->leave->request($this->employee($request), $data, $request->user());
        return ApiResponse::success($req, 'Leave request submitted.', 201);
    }

    public function leaveBalances(Request $request)
    {
        $employee = $this->employee($request);
        $this->leave->ensureBalances($employee, (int) now()->format('Y'));
        return ApiResponse::success(
            $employee->leaveBalances()->with('type')->where('year', (int) now()->format('Y'))->get()
        );
    }

    public function decideLeave(Request $request, LeaveRequest $leaveRequest)
    {
        $data = $request->validate(['decision' => 'required|in:approved,rejected', 'note' => 'nullable|string|max:2000']);
        return ApiResponse::success(
            $this->leave->decide($leaveRequest, $request->user(), $data['decision'], $data['note'] ?? null),
            "Leave {$data['decision']}."
        );
    }

    /* ---- Shifts ---- */
    public function shiftsIndex()
    {
        return ApiResponse::success(ShiftDefinition::where('is_active', true)->get());
    }

    public function storeShift(Request $request)
    {
        abort_unless($request->user()->can('shift.create') || $request->user()->can('shift.manage'), 403);
        $data = $request->validate([
            'code' => 'required|string|max:30|unique:shift_definitions,code',
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_minutes' => 'nullable|integer|min:0|max:480',
            'grace_minutes' => 'nullable|integer|min:0|max:120',
        ]);
        return ApiResponse::success($this->shifts->createDefinition($data), 'Shift created.', 201);
    }

    /* ---- Overtime ---- */
    public function overtime(Request $request)
    {
        return ApiResponse::success($this->overtime->query($request->user())->paginate(15));
    }

    public function requestOvertime(Request $request)
    {
        abort_unless($request->user()->can('overtime.request'), 403);
        $data = $request->validate([
            'work_date' => 'required|date',
            'planned_hours' => 'required|numeric|min:0.5|max:24',
            'reason' => 'required|string|max:2000',
        ]);
        return ApiResponse::success($this->overtime->request($this->employee($request), $data), 'Overtime requested.', 201);
    }

    /* ---- Catalog data ---- */
    public function vacancies()
    {
        return ApiResponse::success(JobVacancy::where('status', 'published')->get());
    }

    public function leaveTypes()
    {
        return ApiResponse::success(LeaveType::where('is_active', true)->get());
    }

    private function employee(Request $request)
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 403, 'No employee record linked to this account.');
        return $employee;
    }
}
