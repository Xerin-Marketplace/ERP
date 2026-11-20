<?php

namespace Modules\Ticketing\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Employee\Models\Employee;
use Modules\Ticketing\Models\ServiceCatalogItem;
use Modules\Ticketing\Models\Ticket;
use Modules\Ticketing\Models\TicketComment;
use Modules\Ticketing\Models\TicketEscalation;
use Modules\Ticketing\Models\TicketTask;
use Modules\Ticketing\Notifications\TicketNotification;

class TicketService
{
    public function __construct(
        private AuditService $audit,
        private TicketNumberService $numbers,
        private SlaService $sla,
    ) {}

    /**
     * Create a ticket. Requester identity + org context come from the
     * authenticated user's employee record — never from the payload.
     */
    public function create(array $data, User $requester): Ticket
    {
        return DB::transaction(function () use ($data, $requester) {
            $emp = $requester->employee;
            $onBehalf = null;
            if (! empty($data['on_behalf_employee_id'])) {
                $onBehalf = Employee::find($data['on_behalf_employee_id']);
            }
            $subject = $onBehalf ?? $emp;

            $ticket = new Ticket;
            $ticket->ticket_number = isset($data['branch_code'])
                ? $this->numbers->itTicket($data['branch_code'])
                : $this->numbers->ticket();
            $ticket->fill([
                'subject' => $data['subject'],
                'description' => $data['description'],
                'ticket_type_id' => $data['ticket_type_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'service_id' => $data['service_id'] ?? null,
                'priority' => $data['priority'] ?? 'normal',
                'severity' => $data['severity'] ?? 'minor',
                'status' => 'submitted',
                'requester_id' => $requester->id,
                'requester_employee_id' => $subject?->id,
                'requester_company_id' => $subject?->company_id,
                'requester_branch_id' => $subject?->branch_id,
                'requester_department_id' => $subject?->department_id,
                'custom_fields' => array_merge($data['custom_fields'] ?? [], $onBehalf ? ['created_on_behalf_by' => $requester->id] : []),
            ]);
            $ticket->save();

            foreach ((array) ($data['tags'] ?? []) as $tag) {
                $ticket->tags()->firstOrCreate(['tag' => Str::limit((string) $tag, 60, '')]);
            }

            $this->history($ticket, null, 'submitted', $requester->id);
            $this->sla->attach($ticket);
            app(AutomationService::class)->run('created', $ticket);

            $this->audit->log('ticket.created', $ticket, new: ['number' => $ticket->ticket_number, 'priority' => $ticket->priority]);

            return $ticket->fresh();
        });
    }

    /** Service-catalog request → prefilled ticket (+ optional approval chain). */
    public function createFromService(ServiceCatalogItem $service, array $data, User $requester): Ticket
    {
        if ($service->requires_attachment && empty($data['attachments'])) {
            throw ValidationException::withMessages(['attachments' => 'This service requires an attachment.']);
        }

        $ticket = $this->create(array_merge($data, [
            'service_id' => $service->id,
            'ticket_type_id' => $data['ticket_type_id'] ?? $service->ticket_type_id,
            'category_id' => $data['category_id'] ?? $service->category_id,
            'priority' => $data['priority'] ?? $service->priority,
        ]), $requester);

        if ($service->default_team_id) {
            app(AssignmentService::class)->assign($ticket, ['team_id' => $service->default_team_id], $requester, 'Service default team');
        }
        if ($service->requires_approval) {
            app(ApprovalService::class)->open($ticket, $service->approval_chain ?? $service->type?->approval_chain ?? ['manager']);
        }

        return $ticket;
    }

    public function transition(Ticket $ticket, string $to, User $actor, ?string $note = null): Ticket
    {
        if (! $ticket->canTransitionTo($to)) {
            throw ValidationException::withMessages(['status' => "Cannot move ticket from {$ticket->status} to {$to}."]);
        }

        return DB::transaction(function () use ($ticket, $to, $actor, $note) {
            $ticket = Ticket::lockForUpdate()->findOrFail($ticket->id);
            $from = $ticket->status;

            $attrs = ['status' => $to];
            if ($to === 'resolved') $attrs['resolved_at'] = now();
            if ($to === 'closed') $attrs['closed_at'] = now();
            if ($to === 'reopened') {
                $attrs['resolved_at'] = null;
                $attrs['closed_at'] = null;
                $attrs['reopen_count'] = $ticket->reopen_count + 1;
            }
            $ticket->update($attrs);

            $this->sla->onStatusChange($ticket->fresh('sla'), $to);
            $this->history($ticket, $from, $to, $actor->id, $note);
            $this->audit->log("ticket.{$to}", $ticket, old: ['status' => $from], new: ['status' => $to]);

            $ticket->requester->notify(new TicketNotification(
                "Ticket {$ticket->ticket_number} → ".ucwords(str_replace('_', ' ', $to)),
                $note ?: "Your ticket status changed to {$to}.",
                route('ticketing.show', $ticket)
            ));

            return $ticket;
        });
    }

    public function comment(Ticket $ticket, User $author, string $body, bool $internal = false): TicketComment
    {
        if ($internal && ! $author->can('ticket.internal_note')) {
            throw ValidationException::withMessages(['internal' => 'Not allowed to add internal notes.']);
        }

        return DB::transaction(function () use ($ticket, $author, $body, $internal) {
            $comment = $ticket->comments()->create([
                'author_id' => $author->id, 'body' => $body,
                'is_internal' => $internal, 'kind' => $internal ? 'note' : 'comment',
            ]);

            if (! $ticket->first_response_at && $author->id !== $ticket->requester_id) {
                $ticket->update(['first_response_at' => now()]);
                $this->sla->markResponded($ticket);
            }

            $this->audit->log($internal ? 'ticket.internal_note' : 'ticket.commented', $comment, metadata: ['ticket_id' => $ticket->id]);

            if (! $internal) {
                $ticket->requester->notify(new TicketNotification(
                    "Update on {$ticket->ticket_number}",
                    Str::limit($body, 120),
                    route('ticketing.show', $ticket)
                ));
            }

            return $comment;
        });
    }

    /** Directory query — scoped by permission + org placement. */
    public function query(User $viewer, array $filters = []): Builder
    {
        $q = Ticket::query()->with(['requester:id,name', 'agent:id,name', 'team:id,name', 'type:id,name', 'category:id,name']);
        $this->scopeFor($q, $viewer);

        return $q->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($w, $s) => $w->where('status', $s))
            ->when($filters['priority'] ?? null, fn ($w, $p) => $w->where('priority', $p))
            ->when($filters['mine'] ?? null, fn ($w) => $w->where('assigned_agent_id', $viewer->id))
            ->when($filters['requested'] ?? null, fn ($w) => $w->where('requester_id', $viewer->id))
            ->when($filters['team_id'] ?? null, fn ($w, $t) => $w->where('assigned_team_id', $t))
            ->when($filters['unassigned'] ?? null, fn ($w) => $w->whereNull('assigned_agent_id')->whereNull('assigned_team_id'))
            ->when($filters['type_id'] ?? null, fn ($w, $t) => $w->where('ticket_type_id', $t))
            ->latest('id');
    }

    /**
     * view_all → everything; ticket.view → own company + own assignments;
     * everyone → own requests.
     */
    public function scopeFor(Builder $q, User $viewer): void
    {
        if ($viewer->can('ticket.view_all')) return;

        $own = $viewer->employee;
        $q->where(function ($w) use ($viewer, $own) {
            $w->where('requester_id', $viewer->id)
              ->orWhere('assigned_agent_id', $viewer->id);

            if ($viewer->can('ticket.view')) {
                if ($own?->company_id) {
                    $w->orWhere('requester_company_id', $own->company_id);
                }
                $teamIds = $own ? \Modules\Ticketing\Models\TicketTeam::whereHas('members', fn ($m) => $m->where('employees.id', $own->id))->pluck('id') : collect();
                if ($teamIds->isNotEmpty()) {
                    $w->orWhereIn('assigned_team_id', $teamIds);
                }
            }
        });
    }

    public function canView(User $viewer, Ticket $ticket): bool
    {
        if ($viewer->can('ticket.view_all')) return true;
        if ($ticket->requester_id === $viewer->id || $ticket->assigned_agent_id === $viewer->id) return true;
        $own = $viewer->employee;
        if ($viewer->can('ticket.view')) {
            if ($own?->company_id && $own->company_id === $ticket->requester_company_id) return true;
            if ($own && $ticket->assigned_team_id
                && \Modules\Ticketing\Models\TicketTeamMember::where('team_id', $ticket->assigned_team_id)->where('employee_id', $own->id)->exists()) {
                return true;
            }
        }
        return false;
    }

    public function dashboard(User $viewer): array
    {
        $base = Ticket::query();
        $this->scopeFor($base, $viewer);
        $count = fn (callable $w) => (clone $base)->where($w)->count();

        return [
            'total' => (clone $base)->count(),
            'open' => $count(fn ($q) => $q->whereNotIn('status', ['resolved', 'closed'])),
            'unassigned' => $count(fn ($q) => $q->whereNull('assigned_agent_id')->whereNull('assigned_team_id')->whereNotIn('status', ['resolved', 'closed'])),
            'critical' => $count(fn ($q) => $q->where('priority', 'critical')->whereNotIn('status', ['resolved', 'closed'])),
            'mine' => $count(fn ($q) => $q->where('assigned_agent_id', $viewer->id)->whereNotIn('status', ['resolved', 'closed'])),
            'requested' => $count(fn ($q) => $q->where('requester_id', $viewer->id)),
            'resolved' => $count(fn ($q) => $q->where('status', 'resolved')),
            'sla_breached' => $count(fn ($q) => $q->whereHas('sla', fn ($s) => $s->where('resolution_breached', true)->orWhere('response_breached', true))),
            'by_status' => (clone $base)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
            'by_priority' => (clone $base)->selectRaw('priority, count(*) as total')->groupBy('priority')->pluck('total', 'priority')->all(),
            'my_tasks' => $viewer->employee
                ? TicketTask::where('assignee_employee_id', $viewer->employee->id)->whereNotIn('status', ['completed', 'cancelled'])->count()
                : 0,
            'escalations' => TicketEscalation::count(),
        ];
    }

    public function reports(User $viewer, ?string $from = null, ?string $to = null): array
    {
        $base = Ticket::query();
        $this->scopeFor($base, $viewer);
        if ($from) $base->whereDate('created_at', '>=', $from);
        if ($to) $base->whereDate('created_at', '<=', $to);

        $resolved = (clone $base)->whereNotNull('resolved_at');
        $sla = \Modules\Ticketing\Models\TicketSlaTracking::whereHas('ticket', fn ($q) => $this->scopeFor($q, $viewer));

        return [
            'created' => (clone $base)->count(),
            'resolved' => (clone $resolved)->count(),
            'closed' => (clone $base)->where('status', 'closed')->count(),
            'reopened' => (clone $base)->where('reopen_count', '>', 0)->count(),
            'avg_first_response_minutes' => (clone $base)->whereNotNull('first_response_at')->get()
                ->avg(fn ($t) => $t->created_at->diffInMinutes($t->first_response_at)),
            'avg_resolution_minutes' => (clone $resolved)->get()
                ->avg(fn ($t) => $t->created_at->diffInMinutes($t->resolved_at)),
            'sla_compliance_pct' => ($total = (clone $sla)->count()) ? round((1 - (clone $sla)->where('resolution_breached', true)->count() / $total) * 100, 1) : 100,
            'sla_breaches' => (clone $sla)->where('resolution_breached', true)->count(),
            'by_company' => (clone $base)->whereNotNull('requester_company_id')->selectRaw('requester_company_id, count(*) t')->groupBy('requester_company_id')->pluck('t', 'requester_company_id')->all(),
            'agent_workload' => (clone $base)->whereNotNull('assigned_agent_id')->whereNotIn('status', ['resolved', 'closed'])
                ->selectRaw('assigned_agent_id, count(*) t')->groupBy('assigned_agent_id')->with('agent:id,name')->get()
                ->map(fn ($r) => ['agent' => $r->agent?->name ?? '—', 'open' => $r->t])->all(),
        ];
    }

    private function history(Ticket $ticket, ?string $from, string $to, int $actorId, ?string $note = null): void
    {
        $ticket->statusHistories()->create(['from_status' => $from, 'to_status' => $to, 'actor_id' => $actorId, 'note' => $note]);
    }
}
