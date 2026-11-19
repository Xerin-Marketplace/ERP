<?php

namespace Modules\HR\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Employee\Models\Employee;
use Modules\HR\Models\DisciplinaryCase;
use Modules\HR\Notifications\HRNotification;

/** Confidential case management — every read/write is permission-gated and audited. */
class DisciplinaryService
{
    public const STATUSES = ['open', 'investigating', 'hearing', 'decided', 'appealed', 'closed'];

    public function __construct(
        private AuditService $audit,
        private HRNumberService $numbers,
    ) {}

    public function openCase(Employee $employee, array $data, User $actor): DisciplinaryCase
    {
        abort_unless($actor->can('disciplinary.manage'), 403);
        abort_unless(in_array($data['severity'] ?? 'minor', ['minor', 'moderate', 'major', 'gross'], true), 422, 'Invalid severity.');

        return DB::transaction(function () use ($employee, $data, $actor) {
            $case = new DisciplinaryCase([
                'employee_id' => $employee->id,
                'category' => $data['category'],
                'incident' => $data['incident'],
                'incident_date' => $data['incident_date'] ?? null,
                'severity' => $data['severity'] ?? 'minor',
                'opened_by' => $actor->id,
                'investigator_id' => $data['investigator_id'] ?? null,
            ]);
            $case->reference = $this->numbers->for('case');
            $case->save();
            $case->actions()->create(['type' => 'note', 'detail' => 'Case opened', 'actor_id' => $actor->id]);
            $this->audit->log('disciplinary.case_opened', $case, new: ['category' => $case->category, 'severity' => $case->severity]);
            return $case->fresh();
        });
    }

    public function addAction(DisciplinaryCase $case, array $data, User $actor)
    {
        abort_unless($actor->can('disciplinary.manage'), 403);
        abort_if($case->status === 'closed', 422, 'Case is closed.');

        $action = $case->actions()->create($data + ['actor_id' => $actor->id]);
        $this->audit->log('disciplinary.action_added', $action, new: ['type' => $action->type]);
        return $action;
    }

    public function recordResponse(DisciplinaryCase $case, string $response, User $actor): DisciplinaryCase
    {
        abort_unless($actor->can('disciplinary.manage'), 403);
        $case->update(['employee_response' => $response]);
        $case->actions()->create(['type' => 'note', 'detail' => 'Employee response recorded', 'actor_id' => $actor->id]);
        $this->audit->log('disciplinary.response_recorded', $case);
        return $case->fresh();
    }

    public function advanceStatus(DisciplinaryCase $case, string $status, User $actor): DisciplinaryCase
    {
        abort_unless($actor->can('disciplinary.manage'), 403);
        abort_unless(in_array($status, self::STATUSES, true), 422, 'Invalid status.');

        $case->update(['status' => $status]);
        $case->actions()->create(['type' => 'note', 'detail' => "Status → {$status}", 'actor_id' => $actor->id]);
        if ($status === 'closed') {
            $case->employee->user?->notify(new HRNotification("Case {$case->reference} closed", 'A disciplinary matter concerning you has been resolved.'));
        }
        $this->audit->log('disciplinary.status_changed', $case, new: ['status' => $status]);
        return $case->fresh();
    }

    /** Confidential: view gated at both service and controller level. */
    public function query(User $viewer)
    {
        abort_unless($viewer->can('disciplinary.view') || $viewer->can('disciplinary.manage'), 403);
        return DisciplinaryCase::with(['employee.user', 'actions.actor'])->latest('id');
    }
}
