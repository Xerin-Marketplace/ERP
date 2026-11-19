<?php

namespace Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FileService;
use Illuminate\Http\Request;
use Modules\Ticketing\Models\Ticket;
use Modules\Ticketing\Models\TicketAttachment;
use Modules\Ticketing\Models\TicketCategory;
use Modules\Ticketing\Models\TicketTeam;
use Modules\Ticketing\Models\TicketType;
use Modules\Ticketing\Services\AssignmentService;
use Modules\Ticketing\Services\TicketService;

class TicketController extends Controller
{
    public function __construct(
        private TicketService $tickets,
        private AssignmentService $assignments,
        private FileService $files,
    ) {}

    public function dashboard(Request $request)
    {
        return view('ticketing::dashboard', ['metrics' => $this->tickets->dashboard($request->user())]);
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->canAny(['ticket.view', 'ticket.view_all']), 403);

        return view('ticketing::tickets.index', [
            'tickets' => $this->tickets->query($request->user(), $request->all())->paginate(20)->withQueryString(),
            'teams' => TicketTeam::active()->get(),
            'types' => TicketType::active()->get(),
            'filters' => $request->only(['search', 'status', 'priority', 'mine', 'unassigned', 'team_id', 'type_id']),
        ]);
    }

    public function my(Request $request)
    {
        $tickets = $this->tickets->query($request->user(), ['requested' => 1] + $request->only('status'))->paginate(20)->withQueryString();

        return view('ticketing::tickets.my', compact('tickets'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('ticket.create'), 403);

        return view('ticketing::tickets.create', [
            'types' => TicketType::active()->orderBy('name')->get(),
            'categories' => TicketCategory::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('ticket.create'), 403);
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'ticket_type_id' => 'nullable|exists:ticket_types,id',
            'category_id' => 'nullable|exists:ticket_categories,id',
            'priority' => 'nullable|in:'.implode(',', Ticket::PRIORITIES),
            'severity' => 'nullable|in:'.implode(',', Ticket::SEVERITIES),
            'attachments.*' => 'file|max:10240',
        ]);

        $ticket = $this->tickets->create($data, $request->user());
        $this->storeAttachments($request, $ticket);

        return redirect()->route('ticketing.show', $ticket)->with('success', "Ticket {$ticket->ticket_number} created.");
    }

    public function show(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        abort_unless($this->tickets->canView($user, $ticket), 403);

        $canInternal = $user->can('ticket.internal_note');
        $ticket->load(['type', 'category', 'service', 'agent:id,name', 'team:id,name', 'assignedDepartment:id,name',
            'requester:id,name,email', 'requesterEmployee.personal', 'sla.policy', 'approvals.approver:id,name',
            'approvals.decider:id,name', 'assignmentHistory.agent:id,name', 'assignmentHistory.team:id,name',
            'statusHistories.actor:id,name', 'attachments.file', 'tasks.assignee.personal',
            'comments' => fn ($q) => $q->when(! $canInternal, fn ($w) => $w->where('is_internal', false))->with('author:id,name')]);

        return view('ticketing::tickets.show', [
            'ticket' => $ticket,
            'canInternal' => $canInternal,
            'canAssign' => $user->can('ticket.assign'),
            'canManage' => $user->canAny(['ticket.update', 'ticket.assign']),
            'agents' => User::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'teams' => TicketTeam::active()->get(),
        ]);
    }

    public function comment(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        abort_unless($this->tickets->canView($user, $ticket), 403);
        abort_unless($user->can('ticket.comment'), 403);
        $data = $request->validate(['body' => 'required|string', 'internal' => 'nullable|boolean']);

        $comment = $this->tickets->comment($ticket, $user, $data['body'], $request->boolean('internal'));
        $this->storeAttachments($request, $ticket, $comment->id);

        return back()->with('success', 'Posted.');
    }

    public function transition(Request $request, Ticket $ticket, string $to)
    {
        $user = $request->user();
        abort_unless($this->tickets->canView($user, $ticket), 403);

        $perm = match ($to) {
            'closed' => 'ticket.close',
            'reopened' => 'ticket.reopen',
            default => 'ticket.update',
        };
        abort_unless($user->can($perm) || ($to === 'reopened' && $ticket->requester_id === $user->id), 403);

        $this->tickets->transition($ticket, $to, $user, $request->input('note'));

        return back()->with('success', 'Ticket updated.');
    }

    public function assign(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()->canAny(['ticket.assign']), 403);
        $data = $request->validate([
            'agent_id' => 'nullable|exists:users,id',
            'team_id' => 'nullable|exists:ticket_teams,id',
            'department_id' => 'nullable|integer',
            'reason' => 'nullable|string',
        ]);

        $this->assignments->assign($ticket, $data, $request->user(), $data['reason'] ?? null);

        return back()->with('success', 'Assignment updated.');
    }

    public function downloadAttachment(Request $request, TicketAttachment $attachment)
    {
        abort_unless($this->tickets->canView($request->user(), $attachment->ticket), 403);

        return $this->files->download($attachment->file);
    }

    private function storeAttachments(Request $request, Ticket $ticket, ?int $commentId = null): void
    {
        if (! $request->hasFile('attachments')) return;
        abort_unless($request->user()->can('ticket.attachment.upload'), 403);
        foreach ($request->file('attachments') as $file) {
            $managed = $this->files->store($file, "tickets/{$ticket->id}", visibility: 'private');
            $ticket->attachments()->create([
                'comment_id' => $commentId, 'managed_file_id' => $managed->id, 'uploaded_by' => $request->user()->id,
            ]);
        }
    }
}
