<?php
namespace Modules\WorkCenter\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Validation\ValidationException;
use Modules\WorkCenter\Models\{WorkTask, TaskProgressUpdate, TaskReview};

/**
 * Assignment → accept → progress history → submit → review → completed.
 * 100% progress ≠ completion when review is required.
 */
class TaskService
{
    private const FLOW = [
        'assigned' => ['accepted','cancelled'],
        'accepted' => ['in_progress','on_hold','cancelled'],
        'in_progress' => ['on_hold','under_review','cancelled'],
        'on_hold' => ['in_progress','cancelled'],
        'under_review' => ['completed','revision_required','cancelled'],
        'revision_required' => ['in_progress','cancelled'],
        'completed' => [], 'rejected' => [], 'cancelled' => [],
    ];

    public function __construct(private AuditService $audit, private ReferenceService $refs) {}

    /** Cross-branch assign: only privileged can leave own branch. */
    public function assign(array $d, User $creator): WorkTask
    {
        $target = User::findOrFail($d['assignee_id']);
        $branch = $d['branch_id'] ?? $target->branch_id;
        $global = $creator->hasAnyRole(['super-admin','group-administrator']);
        if (! $global && $branch !== $creator->branch_id && ! $creator->hasRole('branch-admin')) {
            throw ValidationException::withMessages(['branch' => 'Cannot assign outside your branch.']);
        }
        $task = WorkTask::create($d + [
            'ref' => $this->refs->next('XER-TASK', 'work_tasks'),
            'created_by' => $creator->id, 'branch_id' => $branch,
            'company_id' => $creator->company_id ?? \Modules\Organization\Models\Company::value('id'),
            'status' => 'assigned',
        ]);
        $this->audit->log('task.assigned', $task);
        return $task;
    }

    public function move(WorkTask $t, string $to, User $actor): WorkTask
    {
        if (! in_array($to, self::FLOW[$t->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => "Cannot move {$t->status} → {$to}."]);
        }
        if ($to === 'accepted' || $to === 'under_review') {
            abort_unless($t->assignee_id === $actor->id, 403, 'Only the assignee.');
        }
        $t->update(['status' => $to]);
        $this->audit->log("task.{$to}", $t);
        return $t;
    }

    /** Every update is a NEW history row — never overwritten. */
    public function progress(WorkTask $t, int $pct, User $actor, ?string $note = null, ?int $evidenceFileId = null): TaskProgressUpdate
    {
        abort_unless($t->assignee_id === $actor->id, 403, 'Only the assignee updates progress.');
        $pct = max(0, min(100, $pct));
        $t->update(['progress' => $pct,
            'status' => $pct >= 100 && ! $t->requires_review ? $t->status : $t->status]);
        return TaskProgressUpdate::create(['task_id' => $t->id, 'progress' => $pct,
            'note' => $note, 'evidence_file_id' => $evidenceFileId, 'by_user_id' => $actor->id]);
    }

    /** Submit for review — progress may be 100 but status ≠ completed yet. */
    public function submitForReview(WorkTask $t, User $actor): WorkTask
    {
        abort_unless($t->assignee_id === $actor->id, 403);
        return $this->move($t, 'under_review', $actor);
    }

    public function review(WorkTask $t, string $decision, User $reviewer, ?string $comment = null): WorkTask
    {
        abort_unless(in_array($decision, ['approved','revision']), 422);
        abort_if($t->assignee_id === $reviewer->id, 422, 'Assignee cannot review own task.');
        TaskReview::create(['task_id' => $t->id, 'reviewer_id' => $reviewer->id,
            'decision' => $decision, 'comment' => $comment]);
        return $this->move($t, $decision === 'approved' ? 'completed' : 'revision_required', $reviewer);
    }

    public function complete(WorkTask $t, string $to, User $actor): WorkTask
    {
        $t = $this->move($t, $to, $actor);
        if ($to === 'completed') $t->update(['completed_at' => now(), 'progress' => 100]);
        return $t;
    }

    public function overdue(): \Illuminate\Support\Collection
    {
        return WorkTask::whereNotIn('status', ['completed','cancelled','rejected'])
            ->whereNotNull('due_at')->where('due_at', '<', now())->get();
    }
}
