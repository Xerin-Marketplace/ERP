<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\TransferRequest;
use Modules\Employee\Services\EmployeeService;
use Modules\Employee\Services\TransferService;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;

class TransferController extends Controller
{
    public function __construct(private TransferService $transfers, private EmployeeService $employees) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->canAny(['employee.transfer.request', 'employee.transfer.approve']), 403);

        $transfers = TransferRequest::with('employee.personal', 'requester:id,name', 'toCompany:id,legal_name', 'toBranch:id,name')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()->paginate(20)->withQueryString();

        return view('employee::transfers.index', [
            'transfers' => $transfers,
            'employees' => Employee::where('status', '!=', 'separated')->with('personal')->get(),
            'companies' => Company::where('status', 'active')->orderBy('legal_name')->get(),
            'branches' => Branch::where('status', 'active')->orderBy('name')->get(),
            'departments' => Department::where('status', 'active')->orderBy('name')->get(),
            'positions' => \Modules\Employee\Models\JobPosition::active()->orderBy('title')->get(),
            'canApprove' => $request->user()->can('employee.transfer.approve'),
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        abort_unless($request->user()->can('employee.transfer.request'), 403);

        $data = $request->validate([
            'type' => 'required|in:'.implode(',', TransferRequest::TYPES),
            'to_company_id' => 'nullable|exists:companies,id',
            'to_branch_id' => 'nullable|integer',
            'to_department_id' => 'nullable|integer',
            'to_position_id' => 'nullable|exists:job_positions,id',
            'to_manager_employee_id' => 'nullable|exists:employees,id',
            'reason' => 'nullable|string',
            'effective_date' => 'nullable|date',
        ]);

        $this->transfers->request($employee, $data, $request->user());

        return back()->with('success', 'Transfer request submitted for approval.');
    }

    public function approve(Request $request, TransferRequest $transfer)
    {
        abort_unless($request->user()->can('employee.transfer.approve'), 403);
        $this->transfers->approve($transfer, $request->user(), $request->input('note'));

        return back()->with('success', 'Transfer approved and placement updated.');
    }

    public function reject(Request $request, TransferRequest $transfer)
    {
        abort_unless($request->user()->can('employee.transfer.approve'), 403);
        $request->validate(['note' => 'required|string|min:5']);
        $this->transfers->reject($transfer, $request->user(), $request->note);

        return back()->with('success', 'Transfer rejected.');
    }
}
