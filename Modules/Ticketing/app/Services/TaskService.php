<?php

namespace Modules\Ticketing\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Employee\Models\Employee;
use Modules\Ticketing\Models\Ticket;
use Modules\Ticketing\Models\TicketTask;
use Modules\Ticketing\Notifications\TicketNotification;

class TaskService
{
    public function __construct(private AuditService $audit, private TicketNumberService $numbers) {}

    public function create(array $data, User $creator, ?Ticket $ticket = null): TicketTask
    {
        if (($data['assignee_employee_id'] ?? null)
            && ! Employee::whereKey($data['assignee_employee_id'])->where('status', '!=', 'separated')->exists()) {
            throw ValidationException::withMessages(['assignee_employee_id' => 'Assignee is not an active employee.']);
        }
        if (($data['parent_task_id'] ?? null) && $data['parent_task_id'] === ($data['id'] ?? null)) {
            throw ValidationException::withMessages(['parent_task_id' => 'A task cannot be its own parent.']);
        }

        return DB::transaction(function () use ($data, $creator, $ticket) {
            $task = new TicketTask;
            $task->task_number = $this->numbers->task();
            $task->fill([
                'ticket_id' => $ticket?->id ?? $data['ticket_id'] ?? null,
                'parent_task_id' => $data['parent_task_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'assignee_employee_id' => $data['assignee_employee_id'] ?? null,
                'team_id' => $data['team_id'] ?? null,
                'priority' => $data['priority'] ?? 'normal',
                'status' => $data['assignee_employee_id'] ?? null ? 'assigned' : 'open',
                'start_date' => $data['start_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'estimated_minutes' => $data['estimated_minutes'] ?? null,
                'created_by' => $creator->id,
            ])->save();

            foreach ((array) ($data['depends_on_ids'] ?? []) as $depId) {
                if ($depId != $task->id) {
                    $task->dependencies()->attach($depId);
                }
            }
            foreach ((array) ($data['checklist'] ?? []) as $i => $item) {
                $task->checklist()->create(['title' => $item, 'sort_order' => $i]);
            }

            $task->assignee?->user?->notify(new TicketNotification(
                "Task {$task->task_number} assigned", $task->title,
                route('ticketing.tasks.index')
            ));

            $this->audit->log('task.created', $task, new: ['ticket_id' => $task->ticket_id]);

            return $task->fresh();
        });
    }

    public function updateStatus(TicketTask $task, string $status, User $actor): TicketTask
    {
        abort_unless(in_array($status, TicketTask::STATUSES, true), 422, 'Invalid status.');

        if (in_array($status, ['in_progress', 'completed'], true) && $task->isBlocked()) {
            throw ValidationException::withMessages(['status' => 'Task is blocked by incomplete dependencies.']);
        }

        $task->update([
            'status' => $status,
            'progress' => $status === 'completed' ? 100 : $task->progress,
            'completed_at' => $status === 'completed' ? now() : null,
        ]);

        $this->audit->log("task.{$status}", $task, metadata: ['actor' => $actor->id]);

        return $task;
    }
}
