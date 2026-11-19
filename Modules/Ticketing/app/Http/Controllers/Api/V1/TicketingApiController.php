<?php

namespace Modules\Ticketing\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Modules\Ticketing\Models\ServiceCatalogItem;
use Modules\Ticketing\Models\Ticket;
use Modules\Ticketing\Models\TicketApproval;
use Modules\Ticketing\Models\TicketTask;
use Modules\Ticketing\Services\ApprovalService;
use Modules\Ticketing\Services\AssignmentService;
use Modules\Ticketing\Services\TaskService;
use Modules\Ticketing\Services\TicketService;

class TicketingApiController extends Controller
{
    public function __construct(
        private TicketService $tickets,
        private AssignmentService $assignments,
        private TaskService $tasks,
        private ApprovalService $approvals,
    ) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->canAny(['ticket.view', 'ticket.view_all', 'ticket.create']), 403);

        return ApiResponse::success(
            $this->tickets->query($request->user(), $request->all())->paginate(min((int) $request->get('per_page', 20), 100))
        );
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('ticket.create'), 403);
        $data = $request->validate([
            'subject' => 'required|string|max:255', 'description' => 'required|string',
            'ticket_type_id' => 'nullable|exists:ticket_types,id', 'category_id' => 'nullable|exists:ticket_categories,id',
            'service_id' => 'nullable|exists:service_catalog_items,id',
            'priority' => 'nullable|in:'.implode(',', Ticket::PRIORITIES),
            'severity' => 'nullable|in:'.implode(',', Ticket::SEVERITIES),
            'custom_fields' => 'nullable|array', 'tags' => 'nullable|array',
        ]);

        if ($service = ($data['service_id'] ?? null) ? ServiceCatalogItem::find($data['service_id']) : null) {
            $ticket = $this->tickets->createFromService($service, $data, $request->user());
        } else {
            $ticket = $this->tickets->create($data, $request->user());
        }

        return ApiResponse::success($ticket, 'Ticket created.', 201);
    }

    public function show(Request $request, Ticket $ticket)
    {
        abort_unless($this->tickets->canView($request->user(), $ticket), 403);
        $canInternal = $request->user()->can('ticket.internal_note');
        $ticket->load(['type', 'category', 'agent:id,name', 'team:id,name', 'sla.policy',
            'approvals', 'tasks', 'statusHistories.actor:id,name',
            'comments' => fn ($q) => $q->when(! $canInternal, fn ($w) => $w->where('is_internal', false))->with('author:id,name')]);

        return ApiResponse::success($ticket);
    }

    public function update(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()->can('ticket.update') && $this->tickets->canView($request->user(), $ticket), 403);
        $data = $request->validate([
            'subject' => 'sometimes|string|max:255', 'description' => 'sometimes|string',
            'priority' => 'sometimes|in:'.implode(',', Ticket::PRIORITIES),
            'severity' => 'sometimes|in:'.implode(',', Ticket::SEVERITIES),
            'due_at' => 'nullable|date',
        ]);
        $ticket->update($data);
        app(\App\Services\AuditService::class)->log('ticket.updated', $ticket, new: $data);

        return ApiResponse::success($ticket, 'Updated.');
    }

    public function assign(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()->can('ticket.assign'), 403);
        $data = $request->validate([
            'agent_id' => 'nullable|exists:users,id', 'team_id' => 'nullable|exists:ticket_teams,id',
            'department_id' => 'nullable|integer', 'reason' => 'nullable|string',
        ]);

        return ApiResponse::success($this->assignments->assign($ticket, $data, $request->user()), 'Assigned.');
    }

    public function transition(Request $request, Ticket $ticket, string $to)
    {
        $user = $request->user();
        abort_unless($this->tickets->canView($user, $ticket), 403);
        $perm = match ($to) { 'closed' => 'ticket.close', 'reopened' => 'ticket.reopen', default => 'ticket.update' };
        abort_unless($user->can($perm) || ($to === 'reopened' && $ticket->requester_id === $user->id), 403);

        return ApiResponse::success($this->tickets->transition($ticket, $to, $user, $request->input('note')), 'Status updated.');
    }

    public function comment(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        abort_unless($this->tickets->canView($user, $ticket) && $user->can('ticket.comment'), 403);
        $data = $request->validate(['body' => 'required|string', 'internal' => 'nullable|boolean']);

        return ApiResponse::success($this->tickets->comment($ticket, $user, $data['body'], $request->boolean('internal')), 'Posted.', 201);
    }

    public function approve(Request $request, Ticket $ticket)
    {
        $approval = $ticket->approvals()->pending()->oldest('step')->firstOrFail();

        return ApiResponse::success($this->approvals->decide($approval, $request->user(), 'approved', $request->input('note')), 'Approved.');
    }

    public function reject(Request $request, Ticket $ticket)
    {
        $request->validate(['note' => 'required|string|min:5']);
        $approval = $ticket->approvals()->pending()->oldest('step')->firstOrFail();

        return ApiResponse::success($this->approvals->decide($approval, $request->user(), 'rejected', $request->note), 'Rejected.');
    }

    /* Tasks */
    public function tasks(Request $request)
    {
        abort_unless($request->user()->canAny(['task.view', 'ticket.view', 'ticket.view_all']), 403);
        $q = TicketTask::with('assignee.personal', 'ticket:id,ticket_number,subject')
            ->when($request->status, fn ($w, $s) => $w->where('status', $s))
            ->when($request->mine && $request->user()->employee, fn ($w) => $w->where('assignee_employee_id', $request->user()->employee->id))
            ->latest();

        return ApiResponse::success($q->paginate(20));
    }

    public function storeTask(Request $request)
    {
        abort_unless($request->user()->canAny(['task.create', 'task.assign']), 403);
        $data = $request->validate([
            'ticket_id' => 'nullable|exists:tickets,id', 'title' => 'required|string|max:255',
            'description' => 'nullable|string', 'assignee_employee_id' => 'nullable|exists:employees,id',
            'priority' => 'nullable|in:'.implode(',', Ticket::PRIORITIES),
            'due_date' => 'nullable|date', 'depends_on_ids' => 'nullable|array',
        ]);

        return ApiResponse::success($this->tasks->create($data, $request->user()), 'Task created.', 201);
    }

    public function updateTask(Request $request, TicketTask $task)
    {
        $user = $request->user();
        $isAssignee = $user->employee && $task->assignee_employee_id === $user->employee->id;
        abort_unless($isAssignee || $user->canAny(['task.update', 'task.assign']), 403);
        $request->validate(['status' => 'required|in:'.implode(',', TicketTask::STATUSES)]);

        return ApiResponse::success($this->tasks->updateStatus($task, $request->status, $user), 'Updated.');
    }

    /* Catalog / dashboards */
    public function catalog()
    {
        return ApiResponse::success(ServiceCatalogItem::active()->with('formFields', 'type', 'category')->get());
    }

    public function dashboard(Request $request)
    {
        return ApiResponse::success($this->tickets->dashboard($request->user()));
    }

    public function reports(Request $request)
    {
        abort_unless($request->user()->can('ticket_reports.view'), 403);

        return ApiResponse::success($this->tickets->reports($request->user(), $request->from, $request->to));
    }
}
