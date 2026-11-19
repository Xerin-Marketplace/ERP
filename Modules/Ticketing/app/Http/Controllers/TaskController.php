<?php

namespace Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;
use Modules\Ticketing\Models\Ticket;
use Modules\Ticketing\Models\TicketTask;
use Modules\Ticketing\Services\TaskService;

class TaskController extends Controller
{
    public function __construct(private TaskService $tasks) {}

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->canAny(['task.view', 'ticket.view', 'ticket.view_all']), 403);

        $base = TicketTask::with('assignee.personal', 'ticket:id,ticket_number,subject', 'team:id,name')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->mine && $user->employee, fn ($q) => $q->where('assignee_employee_id', $user->employee->id))
            ->latest('id');

        $board = collect(TicketTask::STATUSES)->mapWithKeys(fn ($s) => [
            $s => (clone $base)->where('status', $s)->limit(25)->get(),
        ]);

        return view('ticketing::tasks.index', [
            'board' => $board,
            'tasks' => $base->paginate(20)->withQueryString(),
            'employees' => Employee::where('status', '!=', 'separated')->with('personal')->get(),
            'tickets' => $user->canAny(['ticket.view', 'ticket.view_all']) ? Ticket::latest('id')->limit(200)->get(['id', 'ticket_number', 'subject']) : collect(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->canAny(['task.create', 'task.assign']), 403);
        $data = $request->validate([
            'ticket_id' => 'nullable|exists:tickets,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assignee_employee_id' => 'nullable|exists:employees,id',
            'priority' => 'nullable|in:'.implode(',', Ticket::PRIORITIES),
            'due_date' => 'nullable|date',
            'estimated_minutes' => 'nullable|integer|min:0',
        ]);

        $task = $this->tasks->create($data, $request->user());

        return back()->with('success', "Task {$task->task_number} created.");
    }

    public function updateStatus(Request $request, TicketTask $task)
    {
        $user = $request->user();
        $isAssignee = $user->employee && $task->assignee_employee_id === $user->employee->id;
        abort_unless($isAssignee || $user->canAny(['task.update', 'task.assign']), 403);

        $request->validate(['status' => 'required|in:'.implode(',', TicketTask::STATUSES)]);
        $this->tasks->updateStatus($task, $request->status, $user);

        return back()->with('success', 'Task updated.');
    }
}
