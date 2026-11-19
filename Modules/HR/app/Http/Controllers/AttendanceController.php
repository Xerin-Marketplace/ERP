<?php

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Models\AttendanceCorrection;
use Modules\HR\Models\AttendanceRecord;
use Modules\HR\Services\AttendanceService;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $svc) {}

    public function index(Request $request)
    {
        $records = $this->svc->query($request->user())
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->date, fn ($q, $d) => $q->where('work_date', $d))
            ->when($request->employee_id, fn ($q, $e) => $q->where('employee_id', $e))
            ->paginate(20)->withQueryString();

        $pendingCorrections = $request->user()->can('attendance.approve')
            ? AttendanceCorrection::with('employee.user')->where('status', 'pending')->latest()->get()
            : collect();

        return view('hr::attendance.index', compact('records', 'pendingCorrections'));
    }

    public function clockIn(Request $request)
    {
        $this->svc->clockIn($request->user()->employee, $request->user());
        return back()->with('status', 'Clocked in.');
    }

    public function clockOut(Request $request)
    {
        $this->svc->clockOut($request->user()->employee, $request->user());
        return back()->with('status', 'Clocked out.');
    }

    public function breakStart(Request $request)
    {
        $this->svc->breakStart($request->user()->employee, $request->user());
        return back()->with('status', 'Break started.');
    }

    public function breakEnd(Request $request)
    {
        $this->svc->breakEnd($request->user()->employee, $request->user());
        return back()->with('status', 'Break ended.');
    }

    public function requestCorrection(Request $request)
    {
        $data = $request->validate([
            'work_date' => 'required|date',
            'type' => 'required|in:missing_clock_in,missing_clock_out,time_adjustment,other',
            'requested_clock_in' => 'nullable|date_format:H:i',
            'requested_clock_out' => 'nullable|date_format:H:i',
            'reason' => 'required|string|max:2000',
            'attendance_id' => 'nullable|exists:attendance_records,id',
        ]);
        $this->svc->requestCorrection($request->user()->employee, $data);
        return back()->with('status', 'Correction requested.');
    }

    public function decideCorrection(Request $request, AttendanceCorrection $correction)
    {
        $data = $request->validate(['decision' => 'required|in:approved,rejected', 'note' => 'nullable|string|max:2000']);
        $this->svc->decideCorrection($correction, $request->user(), $data['decision'], $data['note'] ?? null);
        return back()->with('status', "Correction {$data['decision']}.");
    }
}
