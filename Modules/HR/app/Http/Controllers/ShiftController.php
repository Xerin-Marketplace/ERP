<?php

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Employee\Models\Employee;
use Modules\HR\Models\ShiftAssignment;
use Modules\HR\Models\ShiftDefinition;
use Modules\HR\Services\ShiftService;

class ShiftController extends Controller
{
    public function __construct(private ShiftService $svc) {}

    public function index(Request $request)
    {
        $shifts = ShiftDefinition::withCount(['assignments as active_count' => fn ($q) => $q->where('status', 'active')])->get();
        $assignments = ShiftAssignment::with(['employee.user', 'shift'])->where('status', 'active')->latest('effective_from')->paginate(20);
        $employees = $request->user()->can('shift.assign')
            ? Employee::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_number'])
            : collect();

        return view('hr::shifts.index', compact('shifts', 'assignments', 'employees'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('shift.create') || $request->user()->can('shift.manage'), 403);
        $data = $request->validate([
            'code' => 'required|string|max:30|unique:shift_definitions,code',
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_minutes' => 'nullable|integer|min:0|max:480',
            'grace_minutes' => 'nullable|integer|min:0|max:120',
            'working_days' => 'nullable|array',
            'working_days.*' => 'integer|min:1|max:7',
        ]);
        $this->svc->createDefinition($data);
        return back()->with('status', 'Shift created.');
    }

    public function assign(Request $request, ShiftDefinition $shift)
    {
        abort_unless($request->user()->can('shift.assign') || $request->user()->can('shift.manage'), 403);
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date',
        ]);
        $this->svc->assign($shift, Employee::findOrFail($data['employee_id']), $data, $request->user());
        return back()->with('status', 'Shift assigned.');
    }
}
