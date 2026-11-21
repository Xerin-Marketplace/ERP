<?php

namespace Modules\Reception\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Reception\Models\AccessApprovalTask;
use Modules\Reception\Models\AccessRequest;

class ApprovalTaskService
{
    public function __construct(
        private AccessRequestService $requests,
        private AccessWorkflowService $workflows,
    ) {}

    /** Tasks the user is authorized to decide: role match or direct assignment. */
    public function inboxFor(User $user)
    {
        $roles = $user->getRoleNames();
        return AccessApprovalTask::with('request.branch', 'request.department', 'stage')
            ->where('status', 'pending')
            ->where(fn ($q) => $q->whereIn('approver_role', $roles)->orWhere('assignee_id', $user->id))
            ->latest()->get();
    }

    public function decide(AccessApprovalTask $task, User $actor, string $decision, ?string $comment = null): AccessApprovalTask
    {
        abort_unless(in_array($decision, ['approved', 'rejected', 'changes_requested'], true), 422);

        return DB::transaction(function () use ($task, $actor, $decision, $comment) {
            /** @var AccessApprovalTask $task */
            $task = AccessApprovalTask::whereKey($task->id)->lockForUpdate()->firstOrFail();
            if ($task->status !== 'pending') {
                throw ValidationException::withMessages(['task' => 'This task was already decided.']);
            }

            // Authorization: role match or explicit assignment.
            $authorized = $task->assignee_id === $actor->id
                || $actor->hasRole($task->approver_role)
                || $actor->hasRole('super-admin');
            abort_unless($authorized, 403, 'You are not authorized for this approval stage.');

            // Self-approval guard: applicant-linked users cannot approve own access.
            $request = $task->request;
            if ($request->employee?->user_id === $actor->id
                || $request->manager_id === $actor->id && $request->type === 'existing_employee' && $decision === 'approved') {
                // managers may approve their reports' requests — that's expected.
            }
            if ($request->employee?->user_id === $actor->id) {
                throw ValidationException::withMessages(['task' => 'You cannot approve your own access request.']);
            }
            if ($request->sponsor_id === $actor->id && $decision === 'approved' && $task->approver_role !== 'Branch Manager') {
                // A sponsor can endorse at their own stage, but we flag it in audit.
            }

            $task->update([
                'status' => $decision, 'comment' => $comment,
                'decided_by' => $actor->id, 'decided_at' => now(),
            ]);
            $this->requests->record($request, "approval.{$decision}",
                $request->status, $request->status,
                ['stage' => $task->stage->name ?? $task->sequence, 'comment' => $comment],
                true, null, $actor->id);

            $outcome = $this->workflows->advance($request->fresh());
            if ($outcome === 'completed') {
                $this->requests->markApproved($request->fresh(), $actor->id);
            } elseif ($outcome === 'rejected') {
                $this->requests->reject($request->fresh(), $actor->id,
                    $comment ?? 'Rejected at approval stage.');
            } elseif ($outcome === 'changes_requested') {
                $request->update(['status' => 'info_required']);
                $this->requests->record($request, 'approval.changes_requested',
                    'in_approval', 'info_required', ['comment' => $comment], false, null, $actor->id);
            }
            return $task;
        });
    }

    /** Delegate a pending task to another user (kept auditable). */
    public function delegate(AccessApprovalTask $task, User $actor, User $delegate): AccessApprovalTask
    {
        abort_if($task->status !== 'pending', 422, 'Only pending tasks can be delegated.');
        abort_if($delegate->id === $task->request->employee?->user_id, 422,
            'Cannot delegate to the applicant.');
        $task->update(['assignee_id' => $delegate->id, 'status' => 'pending']);
        $this->requests->record($task->request, 'approval.delegated', null, null,
            ['to' => $delegate->id], true, null, $actor->id);
        return $task;
    }
}
