<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\JobPosition;
use Modules\Employee\Services\EmployeeService;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;

class EmployeeController extends Controller
{
    public function __construct(private EmployeeService $employees) {}

    public function dashboard(Request $request)
    {
        abort_unless($request->user()->canAny(['employee.view', 'employee.view_all']), 403);
        $metrics = $this->employees->dashboard($request->user());

        return view('employee::dashboard', compact('metrics'));
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->canAny(['employee.view', 'employee.view_all']), 403);

        $employees = $this->employees->directory($request->user(), $request->only([
            'search', 'status', 'company_id', 'branch_id', 'department_id', 'position_id', 'employment_type',
        ]))->paginate(20)->withQueryString();

        return view('employee::employees.index', [
            'employees' => $employees,
            'companies' => Company::where('status', 'active')->orderBy('legal_name')->get(['id', 'legal_name']),
            'branches' => Branch::where('status', 'active')->orderBy('name')->get(['id', 'name', 'company_id']),
            'departments' => Department::where('status', 'active')->orderBy('name')->get(['id', 'name', 'company_id']),
            'positions' => JobPosition::orderBy('title')->get(['id', 'title']),
            'filters' => $request->only(['search', 'status', 'company_id', 'branch_id', 'department_id', 'position_id', 'employment_type']),
        ]);
    }

    public function export(Request $request)
    {
        abort_unless($request->user()->can('employee.export'), 403);

        $rows = $this->employees->directory($request->user(), $request->all())->limit(5000)->get();

        $csv = "Employee Number,Name,Official Email,Company,Branch,Department,Position,Type,Status,Start Date\n";
        foreach ($rows as $e) {
            $csv .= implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', [
                $e->employee_number, $e->full_name, $e->official_email,
                $e->company?->legal_name, $e->branch?->name, $e->department?->name,
                $e->position?->title, $e->employment_type, $e->status, $e->start_date?->toDateString(),
            ]))."\n";
        }

        app(\App\Services\AuditService::class)->log('employee.exported', metadata: ['rows' => $rows->count()]);

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="employees-'.now()->format('Ymd').'.csv"',
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('employee.create'), 403);

        return view('employee::employees.form', [
            'employee' => new Employee,
            'companies' => Company::where('status', 'active')->orderBy('legal_name')->get(),
            'branches' => Branch::where('status', 'active')->orderBy('name')->get(),
            'departments' => Department::where('status', 'active')->orderBy('name')->get(),
            'positions' => JobPosition::active()->orderBy('title')->get(),
            'managers' => Employee::where('status', '!=', 'separated')->with('personal')->get(),
            'unlinkedUsers' => \App\Models\User::whereDoesntHave('employee')->where('status', '!=', 'deactivated')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('employee.create'), 403);

        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'preferred_name' => 'nullable|string|max:100',
            'gender' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'nationality' => 'nullable|string|max:100',
            'national_id' => 'nullable|string|max:60',
            'personal_email' => 'nullable|email',
            'phone' => 'nullable|string|max:30',
            'alt_phone' => 'nullable|string|max:30',
            'residential_address' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id|unique:employees,user_id',
            'employee_number' => 'nullable|string|max:40|unique:employees,employee_number',
            'employment_type' => 'required|in:'.implode(',', Employee::TYPES),
            'employee_category' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after_or_equal:start_date',
            'work_location' => 'nullable|string|max:255',
            'official_email' => 'nullable|email',
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|integer',
            'department_id' => 'nullable|integer',
            'job_position_id' => 'nullable|exists:job_positions,id',
            'manager_employee_id' => 'nullable|exists:employees,id',
            'status' => 'nullable|in:onboarding,active',
        ]);

        $employee = $this->employees->create($data, $request->user()->id);

        return redirect()->route('employee.show', $employee)->with('success', "Employee {$employee->employee_number} created.");
    }

    public function show(Request $request, Employee $employee)
    {
        $user = $request->user();
        abort_unless($this->employees->canView($user, $employee), 403);

        $employee->load([
            'personal', 'emergencyContacts', 'company', 'branch', 'department', 'position.grade',
            'manager.personal', 'user.roles', 'currentContract', 'onboarding.tasks', 'offboarding',
            'documents.category', 'employmentRecords' => fn ($q) => $q->latest('start_date'),
            'transfers' => fn ($q) => $q->latest()->with('requester:id,name', 'reviewer:id,name'),
            'changeRequests' => fn ($q) => $q->latest()->with('initiator:id,name', 'approver:id,name'),
            'events' => fn ($q) => $q->latest()->limit(50)->with('actor:id,name'),
            'reportingRelationships' => fn ($q) => $q->current()->with('manager.personal'),
        ]);
        $employee->loadCount('documents');

        $directReports = app(\Modules\Employee\Services\ReportingService::class)->directReports($employee);
        $canManage = $user->can('employee.update');
        $tab = $request->get('tab', 'overview');

        return view('employee::employees.show', compact('employee', 'directReports', 'canManage', 'tab'));
    }

    public function edit(Request $request, Employee $employee)
    {
        abort_unless($request->user()->can('employee.update'), 403);
        $employee->load('personal', 'emergencyContacts');

        return view('employee::employees.form', [
            'employee' => $employee,
            'companies' => Company::where('status', 'active')->orderBy('legal_name')->get(),
            'branches' => Branch::where('status', 'active')->orderBy('name')->get(),
            'departments' => Department::where('status', 'active')->orderBy('name')->get(),
            'positions' => JobPosition::active()->orderBy('title')->get(),
            'managers' => Employee::where('status', '!=', 'separated')->whereKeyNot($employee->id)->with('personal')->get(),
            'unlinkedUsers' => \App\Models\User::whereDoesntHave('employee')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        abort_unless($request->user()->can('employee.update'), 403);

        $data = $request->validate([
            'first_name' => 'sometimes|required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'preferred_name' => 'nullable|string|max:100',
            'gender' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'nationality' => 'nullable|string|max:100',
            'national_id' => 'nullable|string|max:60',
            'personal_email' => 'nullable|email',
            'phone' => 'nullable|string|max:30',
            'alt_phone' => 'nullable|string|max:30',
            'residential_address' => 'nullable|string',
            'employment_type' => 'sometimes|required|in:'.implode(',', Employee::TYPES),
            'employee_category' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'confirmation_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'work_location' => 'nullable|string|max:255',
            'official_email' => 'nullable|email',
            'status' => 'sometimes|in:onboarding,active,suspended',
        ]);

        $this->employees->update($employee, $data, $request->user()->id);

        return redirect()->route('employee.show', $employee)->with('success', 'Employee updated.');
    }
}
