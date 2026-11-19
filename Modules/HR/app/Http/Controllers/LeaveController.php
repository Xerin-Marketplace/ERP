<?php

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Models\LeaveType;
use Modules\HR\Services\LeaveService;

class LeaveController extends Controller
{
    public function __construct(private LeaveService $svc) {}

    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        if ($employee) $this->svc->ensureBalances($employee, (int) now()->format('Y'));

        $requests = $this->svc->query($request->user())
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->paginate(15)->withQueryString();
        $types = LeaveType::where('is_active', true)->orderBy('name')->get();
        $balances = $employee
            ? $employee->leaveBalances()->with('type')->where('year', (int) now()->format('Y'))->get()
            : collect();

        return view('hr::leave.index', compact('requests', 'types', 'balances'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'half_day' => 'nullable|boolean',
            'reason' => 'nullable|string|max:2000',
        ]);
        $this->svc->request($request->user()->employee, $data, $request->user());
        return back()->with('status', 'Leave request submitted.');
    }

    public function approvals(Request $request)
    {
        abort_unless($request->user()->can('leave.approve') || $request->user()->can('leave.manage'), 403);
        $pending = $this->svc->pendingFor($request->user())->paginate(15);
        return view('hr::leave.approvals', compact('pending'));
    }

    public function decide(Request $request, LeaveRequest $leaveRequest)
    {
        $data = $request->validate(['decision' => 'required|in:approved,rejected', 'note' => 'nullable|string|max:2000']);
        $this->svc->decide($leaveRequest, $request->user(), $data['decision'], $data['note'] ?? null);
        return back()->with('status', "Leave {$data['decision']}.");
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest)
    {
        $this->svc->cancel($leaveRequest, $request->user());
        return back()->with('status', 'Leave cancelled.');
    }
}
