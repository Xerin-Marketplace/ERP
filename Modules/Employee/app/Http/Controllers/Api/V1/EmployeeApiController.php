<?php

namespace Modules\Employee\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Modules\Employee\Models\ChangeRequest;
use Modules\Employee\Models\DocumentCategory;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\EmployeeDocument;
use Modules\Employee\Models\JobGrade;
use Modules\Employee\Models\JobPosition;
use Modules\Employee\Models\OffboardingRecord;
use Modules\Employee\Models\TransferRequest;
use Modules\Employee\Services\ChangeService;
use Modules\Employee\Services\DocumentService;
use Modules\Employee\Services\EmployeeService;
use Modules\Employee\Services\OffboardingService;
use Modules\Employee\Services\ReportingService;
use Modules\Employee\Services\TransferService;

class EmployeeApiController extends Controller
{
    public function __construct(
        private EmployeeService $employees,
        private TransferService $transfers,
        private ChangeService $changes,
        private OffboardingService $offboarding,
        private DocumentService $docs,
        private ReportingService $reporting,
    ) {}

    /* ------------------- Directory ------------------- */

    public function index(Request $request)
    {
        abort_unless($request->user()->canAny(['employee.view', 'employee.view_all']), 403);

        $employees = $this->employees->directory($request->user(), $request->all())
            ->paginate(min((int) $request->get('per_page', 20), 100));

        return ApiResponse::success($employees);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('employee.create'), 403);
        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'employment_type' => 'required|in:'.implode(',', Employee::TYPES),
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|integer',
            'department_id' => 'nullable|integer',
            'job_position_id' => 'nullable|exists:job_positions,id',
            'manager_employee_id' => 'nullable|exists:employees,id',
            'user_id' => 'nullable|exists:users,id|unique:employees,user_id',
            'start_date' => 'nullable|date',
            'official_email' => 'nullable|email',
            'phone' => 'nullable|string|max:30',
        ]);

        return ApiResponse::success($this->employees->create($data, $request->user()->id), 'Employee created.', 201);
    }

    public function show(Request $request, Employee $employee)
    {
        abort_unless($this->employees->canView($request->user(), $employee), 403);
        $employee->load(['personal', 'emergencyContacts', 'company:id,legal_name', 'branch:id,name',
            'department:id,name', 'position.grade', 'manager.personal', 'currentContract', 'onboarding.tasks']);

        return ApiResponse::success($employee);
    }

    public function update(Request $request, Employee $employee)
    {
        abort_unless($request->user()->can('employee.update'), 403);
        $data = $request->validate([
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'employment_type' => 'sometimes|in:'.implode(',', Employee::TYPES),
            'status' => 'sometimes|in:onboarding,active,suspended',
            'official_email' => 'nullable|email',
            'phone' => 'nullable|string|max:30',
            'work_location' => 'nullable|string|max:255',
        ]);

        return ApiResponse::success($this->employees->update($employee, $data, $request->user()->id), 'Employee updated.');
    }

    public function dashboard(Request $request)
    {
        abort_unless($request->user()->canAny(['employee.view', 'employee.view_all']), 403);

        return ApiResponse::success($this->employees->dashboard($request->user()));
    }

    /* ------------------- Documents ------------------- */

    public function documents(Request $request, Employee $employee)
    {
        $isSelf = $request->user()->employee?->id === $employee->id;
        abort_unless($isSelf || $this->employees->canView($request->user(), $employee), 403);

        return ApiResponse::success($employee->documents()->with('category', 'uploader:id,name')->get());
    }

    public function uploadDocument(Request $request, Employee $employee)
    {
        $user = $request->user();
        abort_unless($user->employee?->id === $employee->id || $user->can('employee.document.upload'), 403);
        $data = $request->validate([
            'category_id' => 'required|exists:employee_document_categories,id',
            'title' => 'required|string|max:255',
            'expires_at' => 'nullable|date',
            'file' => 'required|file|max:10240',
        ]);

        return ApiResponse::success($this->docs->upload($employee, $data, $request->file('file'), $user->id), 'Uploaded.', 201);
    }

    /* ------------------- History & reporting ------------------- */

    public function history(Request $request, Employee $employee)
    {
        abort_unless($request->user()->canAny(['employee.history.view']) || $this->employees->canView($request->user(), $employee), 403);

        return ApiResponse::success($employee->events()->with('actor:id,name')->paginate(30));
    }

    public function reporting(Request $request, Employee $employee)
    {
        abort_unless($this->employees->canView($request->user(), $employee), 403);
        $employee->load('manager.personal', 'manager.position:id,title');

        return ApiResponse::success([
            'manager' => $employee->manager,
            'direct_reports' => $this->reporting->directReports($employee),
            'chain' => $this->reporting->chain($employee),
        ]);
    }

    /* ------------------- Workflow ------------------- */

    public function transfer(Request $request, Employee $employee)
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

        return ApiResponse::success($this->transfers->request($employee, $data, $request->user()), 'Transfer requested.', 201);
    }

    public function promotion(Request $request, Employee $employee)
    {
        abort_unless($request->user()->can('employee.promotion.request'), 403);
        $data = $request->validate([
            'type' => 'required|in:'.implode(',', ChangeRequest::TYPES),
            'new_values' => 'required|array',
            'reason' => 'nullable|string',
            'effective_date' => 'nullable|date',
        ]);

        return ApiResponse::success(
            $this->changes->request($employee, $data['type'], $data['new_values'], $request->user(), $data['reason'] ?? null, $data['effective_date'] ?? null),
            'Change requested.', 201
        );
    }

    public function offboard(Request $request, Employee $employee)
    {
        abort_unless($request->user()->can('employee.offboarding.manage'), 403);
        $data = $request->validate([
            'reason' => 'required|in:'.implode(',', OffboardingRecord::REASONS),
            'separation_date' => 'required|date',
            'notice_date' => 'nullable|date',
            'handover_notes' => 'nullable|string',
        ]);

        return ApiResponse::success($this->offboarding->initiate($employee, $data, $request->user()), 'Offboarding initiated.', 201);
    }

    /* ------------------- Structure ------------------- */

    public function positions()
    {
        return ApiResponse::success(JobPosition::with('company:id,legal_name', 'department:id,name', 'grade:id,name')->paginate(50));
    }

    public function grades()
    {
        return ApiResponse::success(JobGrade::orderBy('level')->get());
    }

    public function categories()
    {
        return ApiResponse::success(DocumentCategory::where('is_active', true)->get());
    }
}
