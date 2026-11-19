<?php

namespace Modules\Employee\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\TransferRequest;
use Modules\Employee\Notifications\EmployeeNotification;
use Modules\Identity\Services\AssignmentService;

class TransferService
{
    public function __construct(
        private AuditService $audit,
        private EmployeeService $employees,
        private AssignmentService $assignments,
    ) {}

    public function request(Employee $employee, array $data, User $requester): TransferRequest
    {
        // Validate the target placement when org fields are changing
        $targetCompany = $data['to_company_id'] ?? $employee->company_id;
        $placementChanges = ! empty($data['to_company_id']) || ! empty($data['to_branch_id']) || ! empty($data['to_department_id']);
        if ($targetCompany && $placementChanges) {
            $this->assignments->validatePlacement(
                $targetCompany,
                $data['to_branch_id'] ?? $employee->branch_id,
                $data['to_department_id'] ?? $employee->department_id,
            );
        }

        if ($data['to_manager_employee_id'] ?? null) {
            app(ReportingService::class);
            if ((int) $data['to_manager_employee_id'] === $employee->id) {
                throw ValidationException::withMessages(['to_manager_employee_id' => 'An employee cannot be their own manager.']);
            }
            if (! Employee::whereKey($data['to_manager_employee_id'])->where('status', '!=', Employee::STATUS_SEPARATED)->exists()) {
                throw ValidationException::withMessages(['to_manager_employee_id' => 'Target manager is not an active employee.']);
            }
        }

        $transfer = $employee->transfers()->create([
            'type' => $data['type'],
            'from_company_id' => $employee->company_id,
            'from_branch_id' => $employee->branch_id,
            'from_department_id' => $employee->department_id,
            'from_position_id' => $employee->job_position_id,
            'from_manager_employee_id' => $employee->manager_employee_id,
            'to_company_id' => $data['to_company_id'] ?? $employee->company_id,
            'to_branch_id' => $data['to_branch_id'] ?? $employee->branch_id,
            'to_department_id' => $data['to_department_id'] ?? $employee->department_id,
            'to_position_id' => $data['to_position_id'] ?? $employee->job_position_id,
            'to_manager_employee_id' => $data['to_manager_employee_id'] ?? $employee->manager_employee_id,
            'reason' => $data['reason'] ?? null,
            'effective_date' => $data['effective_date'] ?? null,
            'requested_by' => $requester->id,
            'status' => 'pending',
        ]);

        $this->employees->record($employee, 'transfer.requested', "Transfer requested ({$transfer->type})", ['transfer_id' => $transfer->id], $requester->id);
        $this->audit->log('employee.transfer_requested', $transfer, new: ['employee_id' => $employee->id, 'type' => $transfer->type]);

        return $transfer;
    }

    public function approve(TransferRequest $transfer, User $approver, ?string $note = null): TransferRequest
    {
        abort_if($transfer->status !== 'pending', 422, 'Transfer already reviewed.');

        return DB::transaction(function () use ($transfer, $approver, $note) {
            $transfer = TransferRequest::lockForUpdate()->findOrFail($transfer->id);
            $employee = $transfer->employee()->lockForUpdate()->firstOrFail();

            $old = $employee->only(['company_id', 'branch_id', 'department_id', 'job_position_id', 'manager_employee_id']);

            $employee->update([
                'company_id' => $transfer->to_company_id,
                'branch_id' => $transfer->to_branch_id,
                'department_id' => $transfer->to_department_id,
                'job_position_id' => $transfer->to_position_id,
                'manager_employee_id' => $transfer->to_manager_employee_id,
            ]);

            // Keep IAM placement history in sync for linked accounts
            if ($employee->user_id) {
                $this->assignments->assign(
                    $employee->user,
                    ['company_id' => $transfer->to_company_id, 'branch_id' => $transfer->to_branch_id, 'department_id' => $transfer->to_department_id],
                    $approver->id,
                    type: $transfer->type === 'temporary' ? 'temporary' : 'transfer',
                    position: $employee->position?->title,
                    managerId: $employee->manager?->user_id,
                    reason: $transfer->reason,
                    effectiveStart: $transfer->effective_date?->toDateString(),
                );
            }

            $transfer->update(['status' => 'approved', 'reviewed_by' => $approver->id, 'review_note' => $note, 'reviewed_at' => now()]);

            $this->employees->record($employee, 'transfer.approved', "Transfer approved ({$transfer->type})", [
                'from' => $old,
                'to' => $employee->only(array_keys($old)),
            ], $approver->id);
            $this->audit->log('employee.transfer_approved', $transfer, old: $old, new: $employee->only(array_keys($old)));

            $employee->user?->notify(new EmployeeNotification(
                'Transfer Approved',
                "Your {$transfer->type} transfer has been approved" . ($transfer->effective_date ? " effective {$transfer->effective_date->toFormattedDateString()}" : '') . '.',
            ));

            return $transfer;
        });
    }

    public function reject(TransferRequest $transfer, User $approver, string $note): TransferRequest
    {
        abort_if($transfer->status !== 'pending', 422, 'Transfer already reviewed.');
        $transfer->update(['status' => 'rejected', 'reviewed_by' => $approver->id, 'review_note' => $note, 'reviewed_at' => now()]);

        $this->employees->record($transfer->employee, 'transfer.rejected', "Transfer rejected: {$note}", ['transfer_id' => $transfer->id], $approver->id);
        $this->audit->log('employee.transfer_rejected', $transfer, new: ['note' => $note]);
        $transfer->employee->user?->notify(new EmployeeNotification('Transfer Rejected', "Your transfer request was rejected. {$note}"));

        return $transfer;
    }
}
