<?php

namespace Modules\Organization\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Organization\Enums\EntityStatus;
use Modules\Organization\Http\Requests\StoreBranchRequest;
use Modules\Organization\Http\Requests\StoreCompanyRequest;
use Modules\Organization\Http\Requests\StoreDepartmentRequest;
use Modules\Organization\Http\Requests\StoreLocationRequest;
use Modules\Organization\Http\Requests\UpdateBranchRequest;
use Modules\Organization\Http\Requests\UpdateCompanyRequest;
use Modules\Organization\Http\Requests\UpdateDepartmentRequest;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;
use Modules\Organization\Models\Location;
use Modules\Organization\Services\OrganizationService;

class OrganizationApiController extends ApiController
{
    public function __construct(private OrganizationService $org) {}

    public function overview()
    {
        return $this->success($this->org->overview(), 'Organization overview');
    }

    /* Companies */
    public function companies(Request $request)
    {
        $data = Company::with('parent:id,legal_name')
            ->when($request->filled('search'), fn ($q) => $q->where('legal_name', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy('legal_name')
            ->paginate(min((int) $request->input('per_page', 15), 100));

        return $this->success($data, 'Companies');
    }

    public function storeCompany(StoreCompanyRequest $request)
    {
        $company = $this->org->createCompany($request->validated(), $request->user()->id);
        return $this->success($company->load('parent'), 'Company created', 201);
    }

    public function showCompany(Company $company)
    {
        return $this->success($company->load(['parent', 'subsidiaries', 'branches', 'departments']), 'Company');
    }

    public function updateCompany(UpdateCompanyRequest $request, Company $company)
    {
        return $this->success($this->org->updateCompany($company, $request->validated()), 'Company updated');
    }

    /* Branches */
    public function branches(Request $request)
    {
        $data = Branch::with('company:id,legal_name')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('company'), fn ($q) => $q->where('company_id', $request->input('company')))
            ->when($request->filled('region'), fn ($q) => $q->where('region', $request->input('region')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy('name')
            ->paginate(min((int) $request->input('per_page', 15), 100));

        return $this->success($data, 'Branches');
    }

    public function storeBranch(StoreBranchRequest $request)
    {
        $branch = $this->org->createBranch($request->validated(), $request->user()->id);
        return $this->success($branch->load('company'), 'Branch created', 201);
    }

    public function showBranch(Branch $branch)
    {
        return $this->success($branch->load(['company', 'departments', 'locations']), 'Branch');
    }

    public function updateBranch(UpdateBranchRequest $request, Branch $branch)
    {
        return $this->success($this->org->updateBranch($branch, $request->validated()), 'Branch updated');
    }

    /* Locations */
    public function locations(Request $request)
    {
        $data = Location::with('company:id,legal_name', 'branch:id,name')
            ->when($request->filled('company'), fn ($q) => $q->where('company_id', $request->input('company')))
            ->orderBy('name')
            ->paginate(min((int) $request->input('per_page', 15), 100));

        return $this->success($data, 'Locations');
    }

    public function storeLocation(StoreLocationRequest $request)
    {
        return $this->success($this->org->createLocation($request->validated(), $request->user()->id), 'Location created', 201);
    }

    /* Departments */
    public function departments(Request $request)
    {
        $data = Department::with('company:id,legal_name', 'branch:id,name', 'parent:id,name')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('company'), fn ($q) => $q->where('company_id', $request->input('company')))
            ->when($request->filled('branch'), fn ($q) => $q->where('branch_id', $request->input('branch')))
            ->orderBy('name')
            ->paginate(min((int) $request->input('per_page', 15), 100));

        return $this->success($data, 'Departments');
    }

    public function storeDepartment(StoreDepartmentRequest $request)
    {
        $department = $this->org->createDepartment($request->validated(), $request->user()->id);
        return $this->success($department->load(['company', 'branch']), 'Department created', 201);
    }

    public function showDepartment(Department $department)
    {
        return $this->success($department->load(['company', 'branch', 'parent', 'children']), 'Department');
    }

    public function updateDepartment(UpdateDepartmentRequest $request, Department $department)
    {
        return $this->success($this->org->updateDepartment($department, $request->validated()), 'Department updated');
    }

    /* Status transitions — shared for all entities */
    public function status(Request $request, string $entity, int $id)
    {
        $request->validate(['status' => ['required', Rule::enum(EntityStatus::class)]]);
        $status = EntityStatus::from($request->input('status'));

        [$model, $domain] = match ($entity) {
            'companies' => [Company::class, 'company'],
            'branches' => [Branch::class, 'branch'],
            'departments' => [Department::class, 'department'],
            default => abort(404),
        };

        $perm = match ($status) {
            EntityStatus::Active => "{$domain}.activate",
            EntityStatus::Suspended => "{$domain}.suspend",
            EntityStatus::Archived => "{$domain}.archive",
        };
        abort_unless($request->user()->can($perm), 403);

        $record = $model::findOrFail($id);
        $updated = match ($domain) {
            'company' => $this->org->setCompanyStatus($record, $status),
            'branch' => $this->org->setBranchStatus($record, $status),
            'department' => $this->org->setDepartmentStatus($record, $status),
        };

        return $this->success($updated, ucfirst($domain)." {$status->value}");
    }

    /* Structure + audit */
    public function structure()
    {
        return $this->success($this->org->structureTree(), 'Organizational structure');
    }

    public function auditLogs(Request $request)
    {
        $logs = AuditLog::where('resource_type', 'like', 'Modules\\\\Organization%')
            ->orWhere('action', 'like', 'company.%')
            ->orWhere('action', 'like', 'branch.%')
            ->orWhere('action', 'like', 'department.%')
            ->orWhere('action', 'like', 'location.%')
            ->with('user:id,name')
            ->latest()
            ->paginate(min((int) $request->input('per_page', 25), 100));

        return $this->success($logs, 'Organization audit logs');
    }
}
