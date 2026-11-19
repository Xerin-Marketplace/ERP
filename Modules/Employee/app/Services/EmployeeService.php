<?php

namespace Modules\Employee\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\EmployeeEvent;
use Modules\Identity\Services\AssignmentService;

class EmployeeService
{
    public function __construct(
        private AuditService $audit,
        private EmployeeNumberService $numbers,
        private AssignmentService $assignments,
    ) {}

    /**
     * Create an employee record (personal details + optional placement).
     * Placement is validated against the Organization module via Identity's
     * AssignmentService rules.
     */
    public function create(array $data, int $actorId): Employee
    {
        $placement = ($data['company_id'] ?? null)
            ? $this->assignments->validatePlacement(
                $data['company_id'] ?? null,
                $data['branch_id'] ?? null,
                $data['department_id'] ?? null,
            )
            : ['company_id' => null, 'branch_id' => null, 'department_id' => null];

        return DB::transaction(function () use ($data, $placement, $actorId) {
            $employee = new Employee;
            $employee->employee_number = $data['employee_number'] ?? $this->numbers->next();
            $employee->fill([
                'user_id' => $data['user_id'] ?? null,
                'registration_application_id' => $data['registration_application_id'] ?? null,
                'status' => $data['status'] ?? Employee::STATUS_ONBOARDING,
                'employment_type' => $data['employment_type'] ?? 'permanent',
                'employee_category' => $data['employee_category'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'contract_end_date' => $data['contract_end_date'] ?? null,
                'work_location' => $data['work_location'] ?? null,
                'official_email' => $data['official_email'] ?? null,
                'company_id' => $placement['company_id'] ?? null,
                'branch_id' => $placement['branch_id'] ?? null,
                'department_id' => $placement['department_id'] ?? null,
                'job_position_id' => $data['job_position_id'] ?? null,
                'manager_employee_id' => $data['manager_employee_id'] ?? null,
            ])->save();

            $employee->personal()->create([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'preferred_name' => $data['preferred_name'] ?? null,
                'gender' => $data['gender'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'nationality' => $data['nationality'] ?? null,
                'national_id' => $data['national_id'] ?? null,
                'personal_email' => $data['personal_email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'alt_phone' => $data['alt_phone'] ?? null,
                'residential_address' => $data['residential_address'] ?? null,
            ]);

            foreach ((array) ($data['emergency_contacts'] ?? []) as $contact) {
                if (! empty($contact['name'])) {
                    $employee->emergencyContacts()->create($contact);
                }
            }

            $this->record($employee, 'employee.created', 'Employee record created', [
                'employee_number' => $employee->employee_number,
            ], $actorId);
            $this->audit->log('employee.created', $employee, new: [
                'employee_number' => $employee->employee_number,
                'company_id' => $employee->company_id,
            ]);

            return $employee->fresh(['personal', 'company', 'branch', 'department', 'position']);
        });
    }

    public function update(Employee $employee, array $data, int $actorId): Employee
    {
        return DB::transaction(function () use ($employee, $data, $actorId) {
            $old = $employee->only(['status', 'employment_type', 'employee_category', 'start_date', 'confirmation_date', 'contract_end_date', 'work_location', 'official_email']);

            $employee->fill(collect($data)->only(array_keys($old))->all());
            $employee->save();

            $personal = collect($data)->only([
                'first_name', 'middle_name', 'last_name', 'preferred_name', 'gender',
                'date_of_birth', 'nationality', 'national_id', 'personal_email',
                'phone', 'alt_phone', 'residential_address',
            ])->filter(fn ($v) => ! is_null($v))->all();

            if ($personal) {
                $employee->personal()->updateOrCreate(['employee_id' => $employee->id], $personal);
            }

            $this->record($employee, 'employee.updated', 'Employee profile updated', ['changed' => array_keys($data)], $actorId);
            $this->audit->log('employee.updated', $employee, old: $old, new: $employee->only(array_keys($old)));

            return $employee->fresh();
        });
    }

    /**
     * Directory query scoped by the viewer's permissions and placement.
     */
    public function directory(User $viewer, array $filters = []): Builder
    {
        $q = Employee::query()->with(['personal', 'company:id,legal_name', 'branch:id,name', 'department:id,name', 'position:id,title', 'user:id,email,status']);
        $this->scopeFor($q, $viewer);

        return $q->search($filters['search'] ?? null)
            ->inCompany($filters['company_id'] ?? null)
            ->inBranch($filters['branch_id'] ?? null)
            ->inDepartment($filters['department_id'] ?? null)
            ->when($filters['status'] ?? null, fn ($w, $s) => $w->where('status', $s))
            ->when($filters['position_id'] ?? null, fn ($w, $p) => $w->where('job_position_id', $p))
            ->when($filters['employment_type'] ?? null, fn ($w, $t) => $w->where('employment_type', $t))
            ->latest('id');
    }

    /**
     * Data isolation: view_all sees everything; otherwise the viewer is
     * limited to their own company/branch scope, falling back to self-only.
     */
    public function scopeFor(Builder $q, User $viewer): void
    {
        if ($viewer->can('employee.view_all')) {
            return;
        }

        $own = $viewer->employee;

        if (! $own) {
            $q->whereRaw('1 = 0'); // no employee record → no directory visibility
            return;
        }

        if ($viewer->can('employee.view') && $own->company_id) {
            $q->where('company_id', $own->company_id);
            return;
        }

        $q->where('id', $own->id);
    }

    public function canView(User $viewer, Employee $employee): bool
    {
        if ($viewer->can('employee.view_all')) {
            return true;
        }
        $own = $viewer->employee;
        if (! $own) {
            return false;
        }
        if ($own->id === $employee->id) {
            return true;
        }
        return $viewer->can('employee.view') && $own->company_id && $own->company_id === $employee->company_id;
    }

    public function dashboard(User $viewer): array
    {
        $base = Employee::query();
        $this->scopeFor($base, $viewer);

        $count = fn (callable $w) => (clone $base)->where($w)->count();

        return [
            'total' => (clone $base)->count(),
            'active' => $count(fn ($q) => $q->where('status', 'active')),
            'onboarding' => $count(fn ($q) => $q->where('status', 'onboarding')),
            'separated' => $count(fn ($q) => $q->where('status', 'separated')),
            'probation' => $count(fn ($q) => $q->where('employment_type', 'probation')->where('status', '!=', 'separated')),
            'contract' => $count(fn ($q) => $q->where('employment_type', 'contract')->where('status', '!=', 'separated')),
            'contracts_expiring' => $count(fn ($q) => $q->whereNotNull('contract_end_date')->whereBetween('contract_end_date', [now()->toDateString(), now()->addDays(60)->toDateString()])),
            'pending_transfers' => \Modules\Employee\Models\TransferRequest::pending()->count(),
            'pending_offboarding' => \Modules\Employee\Models\OffboardingRecord::where('status', 'pending')->count(),
            'by_company' => (clone $base)->whereNotNull('company_id')
                ->selectRaw('company_id, count(*) as total')->groupBy('company_id')
                ->with('company:id,legal_name')->get()
                ->map(fn ($r) => ['name' => $r->company?->legal_name ?? '—', 'total' => $r->total])->all(),
            'by_department' => (clone $base)->whereNotNull('department_id')
                ->selectRaw('department_id, count(*) as total')->groupBy('department_id')
                ->with('department:id,name')->get()
                ->map(fn ($r) => ['name' => $r->department?->name ?? '—', 'total' => $r->total])->all(),
            'recent_transfers' => \Modules\Employee\Models\TransferRequest::with('employee.personal')
                ->where('status', 'approved')->latest('reviewed_at')->limit(5)->get(),
            'recent_promotions' => \Modules\Employee\Models\ChangeRequest::with('employee.personal')
                ->where('type', 'promotion')->where('status', 'approved')
                ->latest('reviewed_at')->limit(5)->get(),
        ];
    }

    public function record(Employee $employee, string $type, string $description, ?array $metadata = null, ?int $actorId = null): EmployeeEvent
    {
        return $employee->events()->create([
            'actor_user_id' => $actorId ?? auth()->id(),
            'type' => $type,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
