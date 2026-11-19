<?php

namespace Modules\HR\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Employee\Models\Employee;
use Modules\HR\Models\TrainingCertification;
use Modules\HR\Models\TrainingEnrollment;
use Modules\HR\Models\TrainingProgram;
use Modules\HR\Notifications\HRNotification;

class TrainingService
{
    public function __construct(private AuditService $audit) {}

    public function createProgram(array $data): TrainingProgram
    {
        $p = TrainingProgram::create($data);
        $this->audit->log('training.program_created', $p);
        return $p->fresh();
    }

    public function enroll(TrainingProgram $program, Employee $employee, User $actor): TrainingEnrollment
    {
        abort_unless($actor->can('training.manage') || $employee->user_id === $actor->id, 403);
        abort_if($program->fresh()->status !== 'open', 422, 'Program is not open for enrollment.');
        if ($program->capacity && $program->enrollments()->whereIn('status', ['enrolled', 'attended', 'completed'])->count() >= $program->capacity) {
            throw ValidationException::withMessages(['capacity' => 'Program is at capacity.']);
        }

        return DB::transaction(function () use ($program, $employee) {
            $enrollment = TrainingEnrollment::firstOrCreate(
                ['program_id' => $program->id, 'employee_id' => $employee->id]
            );
            $employee->user?->notify(new HRNotification('Training enrollment', "You are enrolled in {$program->title}."));
            $this->audit->log('training.enrolled', $enrollment);
            return $enrollment;
        });
    }

    public function complete(TrainingEnrollment $enrollment, ?int $score, User $actor): TrainingEnrollment
    {
        abort_unless($actor->can('training.manage'), 403);
        abort_if($score !== null && ($score < 0 || $score > 100), 422, 'Score must be 0–100.');

        return DB::transaction(function () use ($enrollment, $score, $actor) {
            $enrollment->update([
                'status' => 'completed', 'score' => $score, 'completed_at' => now(),
            ]);

            // Auto-issue certification for certifying programs
            $program = $enrollment->program;
            if ($program->certifies) {
                TrainingCertification::create([
                    'enrollment_id' => $enrollment->id,
                    'employee_id' => $enrollment->employee_id,
                    'title' => $program->title,
                    'issuer' => $program->provider,
                    'issued_at' => now(),
                    'expires_at' => $program->cert_validity_months ? now()->addMonths($program->cert_validity_months) : null,
                ]);
            }

            $this->audit->log('training.completed', $enrollment, new: ['score' => $score]);
            return $enrollment->fresh();
        });
    }

    /** Certifications expiring within N days (for scheduled reminders). */
    public function expiringCertifications(int $days = 30)
    {
        return TrainingCertification::whereNotNull('expires_at')
            ->whereBetween('expires_at', [now()->toDateString(), now()->addDays($days)->toDateString()])
            ->with('employee.user')->get();
    }
}
