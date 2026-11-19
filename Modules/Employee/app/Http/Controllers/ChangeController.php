<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Models\ChangeRequest;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\JobPosition;
use Modules\Employee\Services\ChangeService;

class ChangeController extends Controller
{
    public function __construct(private ChangeService $changes) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->canAny(['employee.promotion.request', 'employee.promotion.approve']), 403);

        $changes = ChangeRequest::with('employee.personal', 'initiator:id,name', 'approver:id,name')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->latest()->paginate(20)->withQueryString();

        return view('employee::changes.index', [
            'changes' => $changes,
            'employees' => Employee::where('status', '!=', 'separated')->with('personal')->get(),
            'positions' => JobPosition::active()->orderBy('title')->get(),
            'canApprove' => $request->user()->can('employee.promotion.approve'),
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        abort_unless($request->user()->can('employee.promotion.request'), 403);

        $data = $request->validate([
            'type' => 'required|in:'.implode(',', ChangeRequest::TYPES),
            'job_position_id' => 'nullable|exists:job_positions,id',
            'employment_type' => 'nullable|in:'.implode(',', Employee::TYPES),
            'confirmation_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'contract_reference' => 'nullable|string|max:80',
            'reason' => 'nullable|string',
            'effective_date' => 'nullable|date',
        ]);

        $newValues = collect($data)->only(['job_position_id', 'employment_type', 'confirmation_date', 'contract_end_date', 'contract_reference', 'end_date'])->filter()->all();
        if ($data['type'] === 'contract_renewal' && isset($data['contract_end_date'])) {
            $newValues['end_date'] = $data['contract_end_date'];
        }

        $this->changes->request($employee, $data['type'], $newValues, $request->user(), $data['reason'] ?? null, $data['effective_date'] ?? null);

        return back()->with('success', 'Change request submitted for approval.');
    }

    public function approve(Request $request, ChangeRequest $change)
    {
        abort_unless($request->user()->can('employee.promotion.approve'), 403);
        $this->changes->approve($change, $request->user(), $request->input('note'));

        return back()->with('success', 'Change approved and applied.');
    }

    public function reject(Request $request, ChangeRequest $change)
    {
        abort_unless($request->user()->can('employee.promotion.approve'), 403);
        $request->validate(['note' => 'required|string|min:5']);
        $this->changes->reject($change, $request->user(), $request->note);

        return back()->with('success', 'Change rejected.');
    }
}
