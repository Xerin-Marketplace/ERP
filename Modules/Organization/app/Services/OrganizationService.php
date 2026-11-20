<?php

namespace Modules\Organization\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Organization\Enums\EntityStatus;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\BranchAdministrator;
use Modules\Organization\Models\BranchSetting;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;
use Modules\Organization\Models\Location;
use Modules\Organization\Events;

class OrganizationService
{
    public function __construct(private AuditService $audit) {}

    /* ----------------------------- Companies ----------------------------- */

    public function createCompany(array $data, int $actorId): Company
    {
        return DB::transaction(function () use ($data, $actorId) {
            $company = Company::create([...$data, 'created_by' => $actorId]);
            $this->audit->log('company.created', $company, new: $data);
            event(new Events\CompanyCreated($company, $data));
            event(new Events\OrganizationalStructureChanged($company));
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
            event(new Events\CompanyUpdated($company, $data));
            if (($old['parent_company_id'] ?? null) !== ($data['parent_company_id'] ?? null)) {
                event(new Events\OrganizationalStructureChanged($company));
            }
            return $company;
        });
    }

    public function setCompanyStatus(Company $company, EntityStatus $status): Company
    {
        return DB::transaction(function () use ($company, $status) {
            $old = $company->status;
            $company->update(['status' => $status]);
            $this->audit->log("company.".$status->auditAction(), $company,
                old: ['status' => $old->value], new: ['status' => $status->value]);
            event(new Events\CompanyStatusChanged($company, ['from' => $old->value, 'to' => $status->value]));
            if ($status === EntityStatus::Archived) {
                event(new Events\OrganizationalStructureChanged($company));
            }
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
            event(new Events\BranchCreated($branch, $data));
            event(new Events\OrganizationalStructureChanged($branch));
            return $branch;
        });
    }

    public function updateBranch(Branch $branch, array $data): Branch
    {
        return DB::transaction(function () use ($branch, $data) {
            $old = $branch->only(array_keys($data));
            $branch->update($data);
            $this->audit->log('branch.updated', $branch, old: $old, new: $data);
            event(new Events\BranchUpdated($branch, $data));
            return $branch;
        });
    }

    public function setBranchStatus(Branch $branch, EntityStatus $status): Branch
    {
        return DB::transaction(function () use ($branch, $status) {
            $old = $branch->status;
            $branch->update(['status' => $status]);
            $this->audit->log("branch.".$status->auditAction(), $branch,
                old: ['status' => $old->value], new: ['status' => $status->value]);
            event(new Events\BranchStatusChanged($branch, ['from' => $old->value, 'to' => $status->value]));
            return $branch;
        });
    }

    /**
     * Activate a branch — records activated_at, clears deactivated_at.
     * New operational assignments are only accepted while active.
     */
    public function activateBranch(Branch $branch): Branch
    {
        return DB::transaction(function () use ($branch) {
            $old = $branch->status;
            $branch->update([
                'status' => EntityStatus::Active,
                'activated_at' => now(),
                'deactivated_at' => null,
            ]);
            $this->audit->log('branch.activated', $branch,
                old: ['status' => $old->value], new: ['status' => EntityStatus::Active->value]);
            event(new Events\BranchStatusChanged($branch, ['from' => $old->value, 'to' => EntityStatus::Active->value]));
            return $branch;
        });
    }

    /**
     * Soft-deactivate a branch (status → suspended). Never destructive:
     * employees, attendance, tickets and history keep their branch reference.
     */
    public function deactivateBranch(Branch $branch): Branch
    {
        return DB::transaction(function () use ($branch) {
            $old = $branch->status;
            $branch->update([
                'status' => EntityStatus::Suspended,
                'deactivated_at' => now(),
            ]);
            $this->audit->log('branch.deactivated', $branch,
                old: ['status' => $old->value], new: ['status' => EntityStatus::Suspended->value]);
            event(new Events\BranchStatusChanged($branch, ['from' => $old->value, 'to' => EntityStatus::Suspended->value]));
            return $branch;
        });
    }

    /* ------------------------- Branch administrators ---------------------- */

    public function assignBranchAdministrator(Branch $branch, int $userId, int $actorId): BranchAdministrator
    {
        return DB::transaction(function () use ($branch, $userId, $actorId) {
            $existing = BranchAdministrator::where('branch_id', $branch->id)
                ->where('user_id', $userId)->first();

            if ($existing) {
                $existing->update(['revoked_at' => null, 'revoked_by' => null, 'assigned_by' => $actorId]);
                $admin = $existing;
            } else {
                $admin = BranchAdministrator::create([
                    'branch_id' => $branch->id,
                    'user_id' => $userId,
                    'assigned_by' => $actorId,
                ]);
            }

            $this->audit->log('branch.administrator_assigned', $branch,
                new: ['user_id' => $userId]);

            return $admin;
        });
    }

    public function revokeBranchAdministrator(BranchAdministrator $admin, int $actorId): void
    {
        DB::transaction(function () use ($admin, $actorId) {
            $admin->update(['revoked_at' => now(), 'revoked_by' => $actorId]);
            $this->audit->log('branch.administrator_revoked', $admin->branch,
                old: ['user_id' => $admin->user_id]);
        });
    }

    /** Branch IDs a user actively administrates (empty for non-branch admins). */
    public function administratedBranchIds(\App\Models\User $user): array
    {
        return BranchAdministrator::active()->where('user_id', $user->id)
            ->pluck('branch_id')->all();
    }

    /* ---------------------------- Branch settings ------------------------- */

    public function branchSettings(Branch $branch): array
    {
        return $branch->settings->mapWithKeys(fn ($s) => [$s->key => $s->typedValue()])->all();
    }

    public function setBranchSetting(Branch $branch, string $key, mixed $value, int $actorId, string $type = 'string'): void
    {
        DB::transaction(function () use ($branch, $key, $value, $actorId, $type) {
            $stored = $type === 'json' ? json_encode($value) : (string) $value;
            BranchSetting::updateOrCreate(
                ['branch_id' => $branch->id, 'key' => $key],
                ['value' => $stored, 'type' => $type, 'updated_by' => $actorId]
            );
            $this->audit->log('branch.setting_updated', $branch,
                new: ['key' => $key, 'value' => $stored]);
        });
    }

    /* ------------------------------ Locations ----------------------------- */

    public function createLocation(array $data, int $actorId): Location
    {
        return DB::transaction(function () use ($data, $actorId) {
            $location = Location::create([...$data, 'created_by' => $actorId]);
            $this->audit->log('location.created', $location, new: $data);
            event(new Events\LocationCreated($location, $data));
            return $location;
        });
    }

    public function setLocationStatus(Location $location, EntityStatus $status): Location
    {
        return DB::transaction(function () use ($location, $status) {
            $old = $location->status;
            $location->update(['status' => $status]);
            $this->audit->log("location.".$status->auditAction(), $location,
                old: ['status' => $old->value], new: ['status' => $status->value]);
            event(new Events\LocationStatusChanged($location, ['from' => $old->value, 'to' => $status->value]));
            return $location;
        });
    }

    public function updateLocation(Location $location, array $data): Location
    {
        return DB::transaction(function () use ($location, $data) {
            $old = $location->only(array_keys($data));
            $location->update($data);
            $this->audit->log('location.updated', $location, old: $old, new: $data);
            event(new Events\LocationUpdated($location, $data));
            return $location;
        });
    }

    /* ---------------------------- Departments ---------------------------- */

    public function createDepartment(array $data, int $actorId): Department
    {
        return DB::transaction(function () use ($data, $actorId) {
            $department = Department::create([...$data, 'created_by' => $actorId]);
            $this->audit->log('department.created', $department, new: $data);
            event(new Events\DepartmentCreated($department, $data));
            event(new Events\OrganizationalStructureChanged($department));
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
            if ($transferred) {
                event(new Events\DepartmentTransferred($department, $data));
                event(new Events\OrganizationalStructureChanged($department));
            } else {
                event(new Events\DepartmentUpdated($department, $data));
            }
            return $department;
        });
    }

    public function setDepartmentStatus(Department $department, EntityStatus $status): Department
    {
        return DB::transaction(function () use ($department, $status) {
            $old = $department->status;
            $department->update(['status' => $status]);
            $this->audit->log("department.".$status->auditAction(), $department,
                old: ['status' => $old->value], new: ['status' => $status->value]);
            event(new Events\DepartmentStatusChanged($department, ['from' => $old->value, 'to' => $status->value]));
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
        return \Illuminate\Support\Facades\Cache::remember('organization.overview', 60, fn () => $this->computeOverview());
    }

    private function computeOverview(): array
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
