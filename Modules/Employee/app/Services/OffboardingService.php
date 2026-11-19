<?php

namespace Modules\Employee\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\OffboardingRecord;
use Modules\Employee\Notifications\EmployeeNotification;
use Modules\Identity\Services\UserLifecycleService;

class OffboardingService
{
    public function __construct(
        private AuditService $audit,
        private EmployeeService $employees,
        private UserLifecycleService $lifecycle,
    ) {}

    public function initiate(Employee $employee, array $data, User $requester): OffboardingRecord
    {
        if (! in_array($data['reason'], OffboardingRecord::REASONS, true)) {
            throw ValidationException::withMessages(['reason' => 'Invalid separation reason.']);
        }
        if ($employee->offboarding()->whereIn('status', ['pending', 'approved'])->exists()) {
            throw ValidationException::withMessages(['employee' => 'An offboarding is already in progress for this employee.']);
        }

        $record = $employee->offboarding()->create([
            'reason' => $data['reason'],
            'notice_date' => $data['notice_date'] ?? now()->toDateString(),
            'separation_date' => $data['separation_date'],
            'status' => 'pending',
            'requested_by' => $requester->id,
            'checklist' => $data['checklist'] ?? [
                'handover' => false, 'assets_returned' => false, 'final_dues' => false, 'access_revoked' => false,
            ],
            'handover_notes' => $data['handover_notes'] ?? null,
        ]);

        $this->employees->record($employee, 'offboarding.initiated', "Offboarding initiated ({$record->reason})", ['separation_date' => $record->separation_date->toDateString()], $requester->id);
        $this->audit->log('employee.offboarding_initiated', $record, new: ['employee_id' => $employee->id, 'reason' => $record->reason]);

        return $record;
    }

    public function approve(OffboardingRecord $record, User $approver): OffboardingRecord
    {
        abort_if($record->status !== 'pending', 422, 'Offboarding already reviewed.');
        $record->update(['status' => 'approved', 'approved_by' => $approver->id, 'approved_at' => now()]);

        $this->employees->record($record->employee, 'offboarding.approved', 'Offboarding approved', null, $approver->id);
        $this->audit->log('employee.offboarding_approved', $record);

        return $record;
    }

    /**
     * Complete separation: mark employee separated + revoke ERP access via Identity.
     */
    public function complete(OffboardingRecord $record, User $actor, ?array $checklist = null): OffboardingRecord
    {
        abort_if($record->status !== 'approved', 422, 'Offboarding must be approved before completion.');

        return DB::transaction(function () use ($record, $actor, $checklist) {
            $record = OffboardingRecord::lockForUpdate()->findOrFail($record->id);
            $employee = $record->employee()->lockForUpdate()->firstOrFail();

            $checklist = array_merge($record->checklist ?? [], $checklist ?? []);
            $checklist['access_revoked'] = true;

            if ($employee->user) {
                $this->lifecycle->deactivate($employee->user, $actor->id, "Separation: {$record->reason}");
            }

            $employee->update([
                'status' => Employee::STATUS_SEPARATED,
                'separation_date' => $record->separation_date,
            ]);
            $employee->currentContract?->update(['status' => 'terminated', 'end_date' => $record->separation_date]);

            $record->update([
                'status' => 'completed',
                'checklist' => $checklist,
                'access_revoked_at' => now(),
                'completed_at' => now(),
            ]);

            $this->employees->record($employee, 'offboarding.completed', "Employment ended ({$record->reason})", ['separation_date' => $record->separation_date->toDateString()], $actor->id);
            $this->audit->log('employee.offboarding_completed', $record, new: ['employee_id' => $employee->id]);
            $employee->user?->notify(new EmployeeNotification('Employment Separation', 'Your employment record has been closed. Contact HR for clearance details.'));

            return $record;
        });
    }
}
