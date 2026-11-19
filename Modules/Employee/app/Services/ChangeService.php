<?php

namespace Modules\Employee\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Modules\Employee\Models\ChangeRequest;
use Modules\Employee\Models\Employee;
use Modules\Employee\Notifications\EmployeeNotification;

class ChangeService
{
    public function __construct(private AuditService $audit, private EmployeeService $employees) {}

    /**
     * Request a governed employment change (promotion, demotion, confirmation,
     * type change, contract renewal, salary reference for future payroll).
     */
    public function request(Employee $employee, string $type, array $newValues, User $initiator, ?string $reason = null, ?string $effectiveDate = null): ChangeRequest
    {
        $previous = match ($type) {
            'promotion', 'demotion', 'position_change' => ['job_position_id' => $employee->job_position_id],
            'grade_change' => ['job_grade_id' => $employee->position?->job_grade_id],
            'type_change' => ['employment_type' => $employee->employment_type],
            'confirmation' => ['confirmation_date' => $employee->confirmation_date],
            'contract_renewal' => ['contract_end_date' => $employee->contract_end_date],
            default => null,
        };

        $change = $employee->changeRequests()->create([
            'type' => $type,
            'previous_values' => $previous,
            'new_values' => $newValues,
            'reason' => $reason,
            'effective_date' => $effectiveDate,
            'initiated_by' => $initiator->id,
            'status' => 'pending',
        ]);

        $this->employees->record($employee, "change.{$type}.requested", ucwords(str_replace('_', ' ', $type))." requested", ['change_id' => $change->id], $initiator->id);
        $this->audit->log('employee.change_requested', $change, old: $previous, new: $newValues);

        return $change;
    }

    public function approve(ChangeRequest $change, User $approver, ?string $note = null): ChangeRequest
    {
        abort_if($change->status !== 'pending', 422, 'Change already reviewed.');

        return DB::transaction(function () use ($change, $approver, $note) {
            $change = ChangeRequest::lockForUpdate()->findOrFail($change->id);
            $employee = $change->employee()->lockForUpdate()->firstOrFail();
            $new = $change->new_values ?? [];

            match ($change->type) {
                'promotion', 'demotion', 'position_change' => $employee->update(['job_position_id' => $new['job_position_id'] ?? $employee->job_position_id]),
                'type_change' => $employee->update(['employment_type' => $new['employment_type'] ?? $employee->employment_type]),
                'confirmation' => $employee->update(['confirmation_date' => $new['confirmation_date'] ?? now()->toDateString()]),
                'contract_renewal' => $this->renewContract($employee, $new),
                default => null, // grade_change + salary_reference recorded for downstream phases
            };

            $change->update(['status' => 'approved', 'approved_by' => $approver->id, 'review_note' => $note, 'reviewed_at' => now()]);

            $this->employees->record($employee, "change.{$change->type}.approved", ucwords(str_replace('_', ' ', $change->type))." approved", [
                'previous' => $change->previous_values, 'new' => $change->new_values,
            ], $approver->id);
            $this->audit->log('employee.change_approved', $change, old: $change->previous_values, new: $change->new_values);

            $employee->user?->notify(new EmployeeNotification(
                ucwords(str_replace('_', ' ', $change->type)).' Approved',
                "Your {$change->type} has been approved" . ($change->effective_date ? " effective {$change->effective_date->toFormattedDateString()}" : '') . '.',
            ));

            return $change;
        });
    }

    private function renewContract(Employee $employee, array $new): void
    {
        $current = $employee->currentContract;
        $employee->employmentRecords()->create([
            'type' => $employee->employment_type,
            'contract_reference' => $new['contract_reference'] ?? $current?->contract_reference,
            'status' => 'active',
            'start_date' => $new['start_date'] ?? now()->toDateString(),
            'end_date' => $new['end_date'] ?? $new['contract_end_date'] ?? null,
            'renewed_from_id' => $current?->id,
        ]);
        $current?->update(['status' => 'renewed']);
        $employee->update(['contract_end_date' => $new['end_date'] ?? $new['contract_end_date'] ?? null]);
    }

    public function reject(ChangeRequest $change, User $approver, string $note): ChangeRequest
    {
        abort_if($change->status !== 'pending', 422, 'Change already reviewed.');
        $change->update(['status' => 'rejected', 'approved_by' => $approver->id, 'review_note' => $note, 'reviewed_at' => now()]);

        $this->employees->record($change->employee, "change.{$change->type}.rejected", ucwords(str_replace('_', ' ', $change->type))." rejected", ['note' => $note], $approver->id);
        $this->audit->log('employee.change_rejected', $change, new: ['note' => $note]);

        return $change;
    }
}
