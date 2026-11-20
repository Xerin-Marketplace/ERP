<?php

namespace Modules\HR\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;
use Modules\HR\Models\EmployeeCompensation;
use Modules\HR\Services\CompensationService;

class CompensationController extends Controller
{
    public function __construct(private CompensationService $comp) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('hr.compensation.view'), 403);

        return view('hr::compensation.index', [
            'pending' => EmployeeCompensation::where('status', 'pending')
                ->with('employee.personal', 'requester:id,name')->latest()->paginate(20),
            'recent' => EmployeeCompensation::where('status', 'approved')
                ->with('employee.personal')->latest('approved_at')->limit(10)->get(),
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        abort_unless($request->user()->can('hr.compensation.manage'), 403);

        $data = $request->validate([
            'basic_salary' => 'required|numeric|min:0',
            'housing_allowance' => 'nullable|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'medical_allowance' => 'nullable|numeric|min:0',
            'communication_allowance' => 'nullable|numeric|min:0',
            'meal_allowance' => 'nullable|numeric|min:0',
            'other_allowances' => 'nullable|numeric|min:0',
            'overtime_rate' => 'nullable|numeric|min:0',
            'pension_deduction' => 'nullable|numeric|min:0',
            'insurance_deduction' => 'nullable|numeric|min:0',
            'tax_deduction' => 'nullable|numeric|min:0',
            'other_deductions' => 'nullable|numeric|min:0',
            'currency' => 'required|size:3',
            'pay_frequency' => 'required|in:weekly,biweekly,monthly',
            'effective_date' => 'required|date',
            'reason' => 'required|string|max:500',
        ]);

        $this->comp->propose($employee, $data, $request->user());

        return back()->with('success', 'Salary revision proposed — pending approval.');
    }

    public function approve(Request $request, EmployeeCompensation $revision)
    {
        abort_unless($request->user()->can('hr.compensation.approve'), 403);
        $this->comp->approve($revision, $request->user());
        return back()->with('success', 'Compensation approved and activated.');
    }

    public function reject(Request $request, EmployeeCompensation $revision)
    {
        abort_unless($request->user()->can('hr.compensation.approve'), 403);
        $this->comp->reject($revision, $request->user(), $request->input('reason', 'No reason given'));
        return back()->with('success', 'Revision rejected.');
    }
}
