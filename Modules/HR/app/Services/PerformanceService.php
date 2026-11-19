<?php

namespace Modules\HR\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Employee\Models\Employee;
use Modules\HR\Models\PerformanceCycle;
use Modules\HR\Models\PerformanceObjective;
use Modules\HR\Models\PerformanceReview;
use Modules\HR\Notifications\HRNotification;

class PerformanceService
{
    public function __construct(
        private AuditService $audit,
        private HRNumberService $numbers,
    ) {}

    public function createCycle(array $data): PerformanceCycle
    {
        $cycle = PerformanceCycle::create($data);
        $this->audit->log('performance.cycle_created', $cycle);
        return $cycle;
    }

    public function setObjective(PerformanceCycle $cycle, Employee $employee, array $data): PerformanceObjective
    {
        $obj = $cycle->objectives()->create($data + ['employee_id' => $employee->id]);
        $this->audit->log('performance.objective_set', $obj, new: ['title' => $obj->title]);
        return $obj;
    }

    /** Submit a review — self reviews by the employee, manager reviews gated by permission. */
    public function review(PerformanceCycle $cycle, Employee $employee, array $data, User $reviewer): PerformanceReview
    {
        $kind = $data['kind'] ?? 'manager';
        abort_unless(in_array($kind, ['self', 'manager', 'peer'], true), 422, 'Invalid review kind.');

        if ($kind === 'self') {
            abort_unless($employee->user_id === $reviewer->id, 403);
        } else {
            abort_unless($reviewer->can('performance.assess') || $reviewer->can('performance.manage'), 403);
        }
        if (isset($data['rating'])) {
            abort_if($data['rating'] < 1 || $data['rating'] > 5, 422, 'Rating must be 1–5.');
        }

        return DB::transaction(function () use ($cycle, $employee, $data, $reviewer, $kind) {
            $review = PerformanceReview::firstOrNew(
                ['cycle_id' => $cycle->id, 'employee_id' => $employee->id, 'kind' => $kind]
            );
            $review->fill([
                'reviewer_id' => $reviewer->id,
                'rating' => $data['rating'] ?? null,
                'summary' => $data['summary'] ?? null,
                'improvement_plan' => $data['improvement_plan'] ?? null,
                'status' => 'submitted',
            ]);
            if (! $review->exists) {
                $review->reference = $this->numbers->for('review');
            }
            $review->save();
            $this->audit->log('performance.review_submitted', $review, new: ['kind' => $kind, 'rating' => $review->rating]);
            $employee->user?->notify(new HRNotification("Performance review {$review->reference}", "A {$kind} review was submitted for {$cycle->name}."));
            return $review;
        });
    }

    public function query(User $viewer)
    {
        $q = PerformanceReview::with(['employee.user', 'cycle'])->latest('id');
        if ($viewer->can('performance.manage') || $viewer->can('hr.dashboard.view')) return $q;
        if ($viewer->can('performance.assess') || $viewer->can('performance.view')) {
            $own = $viewer->employee;
            return $q->where(fn ($w) => $w->where('employee_id', $own?->id ?? -1)
                ->orWhereHas('employee', fn ($e) => $e->where('department_id', $own?->department_id ?? -1)));
        }
        return $q->where('employee_id', $viewer->employee?->id ?? -1);
    }
}
