<?php

namespace Modules\Identity\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Enums\AssignmentStatus;
use Modules\Identity\Models\EmployeeAssignment;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Department;

/**
 * Organizational assignment — the ONLY path that writes a user's
 * company/branch/department placement. Append-only history.
 */
class AssignmentService
{
    public function __construct(private AuditService $audit) {}

    /**
     * Validate an organizational placement strictly against the
     * Organization module (never trust applicant-supplied values).
     *
     * @return array{company_id:int, branch_id:?int, department_id:?int}
     */
    public function validatePlacement(?int $companyId, ?int $branchId, ?int $departmentId): array
    {
        if (! $companyId) {
            throw ValidationException::withMessages(['company_id' => 'A company assignment is required.']);
        }

        $company = \Modules\Organization\Models\Company::find($companyId);
        if (! $company || $company->status !== \Modules\Organization\Enums\EntityStatus::Active) {
            throw ValidationException::withMessages(['company_id' => 'The selected company is not active.']);
        }

        if ($branchId) {
            $branch = Branch::find($branchId);
            if (! $branch || $branch->company_id !== $company->id) {
                throw ValidationException::withMessages(['branch_id' => 'The selected branch does not belong to the selected company.']);
            }
            if ($branch->status !== \Modules\Organization\Enums\EntityStatus::Active) {
                throw ValidationException::withMessages(['branch_id' => 'The selected branch is not active.']);
            }
        }

        if ($departmentId) {
            $dept = Department::find($departmentId);
            if (! $dept || $dept->company_id !== $company->id) {
                throw ValidationException::withMessages(['department_id' => 'The selected department does not belong to the selected company.']);
            }
            if ($dept->status !== \Modules\Organization\Enums\EntityStatus::Active) {
                throw ValidationException::withMessages(['department_id' => 'The selected department is not active.']);
            }
            // A branch-scoped department can only pair with its own branch
            if ($dept->branch_id && $dept->branch_id !== $branchId) {
                throw ValidationException::withMessages(['department_id' => 'The selected department is scoped to a different branch.']);
            }
            if ($branchId && $dept->branch_id === null) {
                // company-wide department assigned to a branch member — allowed
            }
        }

        return ['company_id' => $companyId, 'branch_id' => $branchId, 'department_id' => $departmentId];
    }

    /**
     * Create a new assignment record, ending the currently active one.
     * History is never overwritten.
     */
    public function assign(
        User $user,
        array $placement,
        ?int $assignedBy,
        string $type = 'new',
        ?string $position = null,
        ?int $managerId = null,
        ?string $reason = null,
        ?string $effectiveStart = null,
        string $status = 'active',
    ): EmployeeAssignment {
        return DB::transaction(function () use ($user, $placement, $assignedBy, $type, $position, $managerId, $reason, $effectiveStart, $status) {
            $user->assignments()->current()->update([
                'effective_end' => now()->toDateString(),
                'status' => AssignmentStatus::Ended->value,
            ]);

            $assignment = EmployeeAssignment::create([
                'user_id' => $user->id,
                'company_id' => $placement['company_id'],
                'branch_id' => $placement['branch_id'] ?? null,
                'department_id' => $placement['department_id'] ?? null,
                'position' => $position,
                'manager_user_id' => $managerId,
                'assigned_by' => $assignedBy,
                'effective_start' => $effectiveStart ?? now()->toDateString(),
                'status' => $status,
                'assignment_type' => $type,
                'reason' => $reason,
            ]);

            // Denormalized convenience pointer used by the existing UI
            $user->update(['branch_id' => $placement['branch_id'] ?? null]);

            $this->audit->log("assignment.{$type}", $assignment, new: [
                'user_id' => $user->id,
                'company_id' => $placement['company_id'],
                'branch_id' => $placement['branch_id'] ?? null,
                'department_id' => $placement['department_id'] ?? null,
                'position' => $position,
            ]);

            return $assignment;
        });
    }
}
