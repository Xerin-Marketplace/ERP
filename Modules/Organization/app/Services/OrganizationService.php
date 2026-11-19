<?php

namespace Modules\Organization\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Organization\Enums\EntityStatus;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;
use Modules\Organization\Models\Location;

class OrganizationService
{
    public function __construct(private AuditService $audit) {}

    /* ----------------------------- Companies ----------------------------- */

    public function createCompany(array $data, int $actorId): Company
    {
        return DB::transaction(function () use ($data, $actorId) {
            $company = Company::create([...$data, 'created_by' => $actorId]);
            $this->audit->log('company.created', $company, new: $data);
            return $company;
        });
    }

    public function updateCompany(Company $company, array $data): Company
    {
        if (isset($data['parent_company_id'])) {
            $this->assertCompanyParentValid($company, $data['parent_company_id']);
        }

        return DB::transaction(function () use ($company, $data) {
            $old = $company->only(array_keys($data));
            $company->update($data);
            $this->audit->log('company.updated', $company, old: $old, new: $data);
            return $company;
        });
    }

    public function setCompanyStatus(Company $company, EntityStatus $status): Company
    {
        return DB::transaction(function () use ($company, $status) {
            $old = $company->status;
            $company->update(['status' => $status]);
            $this->audit->log("company.{$status->value}d", $company,
                old: ['status' => $old->value], new: ['status' => $status->value]);
            return $company;
        });
    }

    public function assertCompanyParentValid(Company $company, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }
        if ($parentId === $company->id) {
            throw ValidationException::withMessages(['parent_company_id' => 'A company cannot be its own parent.']);
        }
        if (in_array($parentId, $company->ancestorIds()) === false && $company->subsidiaries()->where('id', $parentId)->exists() === false) {
            return; // parent not in subtree — safe
        }
        if (in_array($parentId, $this->descendantIds($company))) {
            throw ValidationException::withMessages(['parent_company_id' => 'Circular relationship detected — the selected parent is a descendant of this company.']);
        }
    }

    private function descendantIds(Company $company): array
    {
        $ids = [];
        $stack = $company->subsidiaries()->pluck('id')->all();
        while ($stack) {
            $id = array_pop($stack);
            $ids[] = $id;
            $stack = array_merge($stack, Company::where('parent_company_id', $id)->pluck('id')->all());
        }
        return $ids;
    }

    /* ----------------------------- Branches ------------------------------ */

    public function createBranch(array $data, int $actorId): Branch
    {
        return DB::transaction(function () use ($data, $actorId) {
            $branch = Branch::create([...$data, 'created_by' => $actorId]);
            $this->audit->log('branch.created', $branch, new: $data);
            return $branch;
        });
    }

    public function updateBranch(Branch $branch, array $data): Branch
    {
        return DB::transaction(function () use ($branch, $data) {
            $old = $branch->only(array_keys($data));
            $branch->update($data);
            $this->audit->log('branch.updated', $branch, old: $old, new: $data);
            return $branch;
        });
    }

    public function setBranchStatus(Branch $branch, EntityStatus $status): Branch
    {
        return DB::transaction(function () use ($branch, $status) {
            $old = $branch->status;
            $branch->update(['status' => $status]);
            $this->audit->log("branch.{$status->value}d", $branch,
                old: ['status' => $old->value], new: ['status' => $status->value]);
            return $branch;
        });
    }

    /* ----------------------------- Locations ----------------------------- */

    public function createLocation(array $data, int $actorId): Location
    {
        return DB::transaction(function () use ($data, $actorId) {
            $location = Location::create([...$data, 'created_by' => $actorId]);
            $this->audit->log('location.created', $location, new: $data);
            return $location;
        });
    }

    public function updateLocation(Location $location, array $data): Location
    {
        return DB::transaction(function () use ($location, $data) {
            $old = $location->only(array_keys($data));
            $location->update($data);
            $this->audit->log('location.updated', $location, old: $old, new: $data);
            return $location;
        });
    }

    /* ---------------------------- Departments ---------------------------- */

    public function createDepartment(array $data, int $actorId): Department
    {
        return DB::transaction(function () use ($data, $actorId) {
            $department = Department::create([...$data, 'created_by' => $actorId]);
            $this->audit->log('department.created', $department, new: $data);
            return $department;
        });
    }

    public function updateDepartment(Department $department, array $data): Department
    {
        if (array_key_exists('parent_id', $data)) {
            $this->assertDepartmentParentValid($department, $data['parent_id']);
        }

        return DB::transaction(function () use ($department, $data) {
            $old = $department->only(array_keys($data));
            $department->update($data);
            $transferred = array_key_exists('parent_id', $data)
                && ($old['parent_id'] ?? null) !== ($data['parent_id'] ?? null);
            $this->audit->log($transferred ? 'department.transferred' : 'department.updated', $department, old: $old, new: $data);
            return $department;
        });
    }

    public function setDepartmentStatus(Department $department, EntityStatus $status): Department
    {
        return DB::transaction(function () use ($department, $status) {
            $old = $department->status;
            $department->update(['status' => $status]);
            $this->audit->log("department.{$status->value}d", $department,
                old: ['status' => $old->value], new: ['status' => $status->value]);
            return $department;
        });
    }

    public function assertDepartmentParentValid(Department $department, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }
        if ($parentId === $department->id) {
            throw ValidationException::withMessages(['parent_id' => 'A department cannot be its own parent.']);
        }
        if (in_array($parentId, $this->departmentDescendantIds($department))) {
            throw ValidationException::withMessages(['parent_id' => 'Circular relationship detected — the selected parent is a descendant of this department.']);
        }
    }

    private function departmentDescendantIds(Department $department): array
    {
        $ids = [];
        $stack = $department->children()->pluck('id')->all();
        while ($stack) {
            $id = array_pop($stack);
            $ids[] = $id;
            $stack = array_merge($stack, Department::where('parent_id', $id)->pluck('id')->all());
        }
        return $ids;
    }

    /* ------------------------------ Overview ----------------------------- */

    public function overview(): array
    {
        return [
            'companies_total' => Company::count(),
            'companies_active' => Company::active()->count(),
            'subsidiaries' => Company::whereNotNull('parent_company_id')->count(),
            'branches_total' => Branch::count(),
            'branches_active' => Branch::active()->count(),
            'branches_suspended' => Branch::where('status', EntityStatus::Suspended->value)->count(),
            'departments_total' => Department::count(),
            'departments_active' => Department::active()->count(),
            'locations_total' => Location::count(),
            'companies_by_type' => Company::selectRaw('company_type, count(*) as total')->groupBy('company_type')->pluck('total', 'company_type'),
            'branches_by_status' => Branch::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'branches_by_region' => Branch::selectRaw("COALESCE(NULLIF(region,''),'Unspecified') as r, count(*) as total")->groupBy('r')->orderByDesc('total')->limit(8)->pluck('total', 'r'),
            'departments_by_status' => Department::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'recent_branches' => Branch::with('company:id,legal_name')->latest()->limit(5)->get(),
            'recent_companies' => Company::latest()->limit(5)->get(),
        ];
    }

    /* ------------------------------ Structure ---------------------------- */

    public function structureTree(): array
    {
        $companies = Company::with([
            'branches' => fn ($q) => $q->orderBy('name'),
            'branches.departments' => fn ($q) => $q->whereNull('parent_id')->orderBy('name'),
            'branches.departments.children',
            'departments' => fn ($q) => $q->whereNull('branch_id')->whereNull('parent_id')->orderBy('name'),
            'departments.children',
        ])->whereNull('parent_company_id')->orderBy('legal_name')->get();

        return $companies->map(fn (Company $c) => $this->companyNode($c))->all();
    }

    private function companyNode(Company $c): array
    {
        return [
            'id' => "company-{$c->id}", 'type' => 'company', 'label' => $c->trading_name ?: $c->legal_name,
            'status' => $c->status->value, 'meta' => $c->company_type,
            'children' => [
                ...$c->subsidiaries()->with(['branches.departments.children', 'departments.children'])->orderBy('legal_name')->get()->map(fn ($s) => $this->companyNode($s)),
                ...$c->branches->map(fn (Branch $b) => $this->branchNode($b)),
                ...$c->departments->map(fn (Department $d) => $this->departmentNode($d)),
            ],
        ];
    }

    private function branchNode(Branch $b): array
    {
        return [
            'id' => "branch-{$b->id}", 'type' => 'branch', 'label' => $b->name,
            'status' => $b->status->value, 'meta' => $b->branch_type,
            'children' => $b->departments->map(fn (Department $d) => $this->departmentNode($d))->all(),
        ];
    }

    private function departmentNode(Department $d): array
    {
        return [
            'id' => "department-{$d->id}", 'type' => 'department', 'label' => $d->name,
            'status' => $d->status->value, 'meta' => $d->code,
            'children' => $d->children->map(fn (Department $c) => $this->departmentNode($c))->all(),
        ];
    }
}
