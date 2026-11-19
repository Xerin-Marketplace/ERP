<?php

namespace Modules\Employee\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\OnboardingRecord;
use Modules\Employee\Models\OnboardingTask;
use Modules\Employee\Notifications\EmployeeNotification;

class OnboardingService
{
    public const DEFAULT_TASKS = [
        'Verify employee information',
        'Complete employment documentation',
        'Assign official email',
        'Confirm company and branch assignment',
        'Confirm department assignment',
        'Confirm reporting manager',
        'Complete orientation',
        'Confirm ERP access provisioning',
    ];

    public function __construct(private AuditService $audit, private EmployeeService $employees) {}

    public function start(Employee $employee, int $actorId, ?array $tasks = null): OnboardingRecord
    {
        return DB::transaction(function () use ($employee, $actorId, $tasks) {
            $record = $employee->onboarding()->firstOrCreate([], [
                'status' => 'in_progress', 'started_by' => $actorId, 'started_at' => now(),
            ]);

            if ($record->tasks()->doesntExist()) {
                foreach (($tasks ?? self::DEFAULT_TASKS) as $i => $title) {
                    $record->tasks()->create([
                        'title' => is_array($title) ? $title['title'] : $title,
                        'description' => is_array($title) ? ($title['description'] ?? null) : null,
                        'sort_order' => $i,
                        'is_required' => is_array($title) ? ($title['is_required'] ?? true) : true,
                    ]);
                }
            }

            $this->employees->record($employee, 'onboarding.started', 'Onboarding started', null, $actorId);
            $this->audit->log('employee.onboarding_started', $record, new: ['employee_id' => $employee->id]);

            return $record->fresh('tasks');
        });
    }

    public function updateTask(OnboardingTask $task, string $status, int $actorId): OnboardingTask
    {
        abort_unless(in_array($status, ['pending', 'in_progress', 'completed', 'skipped'], true), 422, 'Invalid status.');

        $task->update([
            'status' => $status,
            'completed_by' => $status === 'completed' ? $actorId : null,
            'completed_at' => $status === 'completed' ? now() : null,
        ]);

        $employee = $task->record->employee;
        $this->employees->record($employee, 'onboarding.task', "Onboarding task '{$task->title}' → {$status}", null, $actorId);

        // Auto-complete when all required tasks are done
        $record = $task->record->fresh('tasks');
        if ($record->tasks->where('is_required', true)->every(fn ($t) => in_array($t->status, ['completed', 'skipped'], true))) {
            $record->update(['status' => 'completed', 'completed_at' => now()]);
            if ($employee->status === Employee::STATUS_ONBOARDING) {
                $employee->update(['status' => Employee::STATUS_ACTIVE]);
            }
            $this->employees->record($employee, 'onboarding.completed', 'Onboarding completed — employee activated', null, $actorId);
            $employee->user?->notify(new EmployeeNotification('Onboarding Complete', 'Your onboarding is complete. Welcome to Xerin Group!'));
        }

        return $task;
    }
}
