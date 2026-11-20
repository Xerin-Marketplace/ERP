<?php

namespace Modules\HR\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Employee\Models\Employee;
use Modules\HR\Models\EmployeeCompensation;

class CompensationService
{
    public function __construct(private AuditService $audit) {}

    /** Current approved package. */
    public function current(Employee $employee): ?EmployeeCompensation
    {
        return $employee->compensations()
            ->where('status', 'approved')
            ->whereDate('effective_date', '<=', now())
            ->latest('effective_date')->first();
    }

    /** New revision starts as pending — never overwrites history. */
    public function propose(Employee $employee, array $data, User $actor): EmployeeCompensation
    {
        return DB::transaction(function () use ($employee, $data, $actor) {
            $revision = $employee->compensations()->create(
                array_merge($data, ['status' => 'pending', 'requested_by' => $actor->id])
            );

            $this->audit->log('hr.compensation.proposed', $revision,
                new: ['basic' => $revision->basic_salary, 'effective' => $revision->effective_date],
                metadata: ['actor' => $actor->id]);

            return $revision;
        });
    }

    /** Approving supersedes the currently active package. */
    public function approve(EmployeeCompensation $revision, User $actor): EmployeeCompensation
    {
        abort_if($revision->status !== 'pending', 422, 'Only pending revisions can be approved.');
        abort_if($revision->requested_by === $actor->id, 422, 'Requester cannot approve own salary change.');

        return DB::transaction(function () use ($revision, $actor) {
            $current = $this->current($revision->employee);
            $current?->update(['status' => 'superseded']);

            $revision->update([
                'status' => 'approved', 'approved_by' => $actor->id,
                'approved_at' => now(), 'supersedes_id' => $current?->id,
            ]);

            $this->audit->log('hr.compensation.approved', $revision,
                old: $current ? ['basic' => $current->basic_salary, 'gross' => $current->gross()] : null,
                new: ['basic' => $revision->basic_salary, 'gross' => $revision->gross()],
                metadata: ['actor' => $actor->id]);

            $revision->employee->events()->create([
                'actor_user_id' => $actor->id,
                'type' => 'salary_revision',
                'description' => "Salary revised to {$revision->currency} {$revision->gross()} gross effective {$revision->effective_date->format('Y-m-d')}",
            ]);

            return $revision->fresh();
        });
    }

    public function reject(EmployeeCompensation $revision, User $actor, string $reason): EmployeeCompensation
    {
        abort_if($revision->status !== 'pending', 422, 'Only pending revisions can be rejected.');
        $revision->update(['status' => 'rejected', 'reason' => $revision->reason."\nRejected: {$reason}"]);
        $this->audit->log('hr.compensation.rejected', $revision, metadata: ['actor' => $actor->id, 'reason' => $reason]);
        return $revision;
    }
}
