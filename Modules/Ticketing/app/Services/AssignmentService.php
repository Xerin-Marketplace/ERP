<?php

namespace Modules\Ticketing\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Ticketing\Models\Ticket;
use Modules\Ticketing\Models\TicketTeam;
use Modules\Ticketing\Notifications\TicketNotification;

class AssignmentService
{
    public function __construct(private AuditService $audit, private TicketService $tickets) {}

    /** Assign a team and/or agent. Full history preserved per assignment row. */
    public function assign(Ticket $ticket, array $data, User $actor, ?string $reason = null): Ticket
    {
        $agent = null;
        if ($agentId = ($data['agent_id'] ?? null)) {
            $agent = User::find($agentId);
            if (! $agent || ! $agent->isActive()) {
                throw ValidationException::withMessages(['agent_id' => 'The selected agent is not an active account.']);
            }
            if (! $agent->canAny(['ticket.view', 'ticket.view_all', 'ticket.assign'])) {
                throw ValidationException::withMessages(['agent_id' => 'The selected user lacks ticket access.']);
            }
        }
        if ($teamId = ($data['team_id'] ?? null)) {
            if (! TicketTeam::active()->whereKey($teamId)->exists()) {
                throw ValidationException::withMessages(['team_id' => 'The selected team is not active.']);
            }
        }

        return DB::transaction(function () use ($ticket, $data, $agent, $actor, $reason) {
            $old = $ticket->only(['assigned_agent_id', 'assigned_team_id', 'assigned_department_id']);

            $ticket->update([
                'assigned_agent_id' => $agent?->id ?? $ticket->assigned_agent_id,
                'assigned_team_id' => $data['team_id'] ?? $ticket->assigned_team_id,
                'assigned_department_id' => $data['department_id'] ?? $ticket->assigned_department_id,
            ]);

            $ticket->assignmentHistory()->create([
                'team_id' => $data['team_id'] ?? null,
                'agent_id' => $agent?->id,
                'department_id' => $data['department_id'] ?? null,
                'assigned_by' => $actor->id,
                'reason' => $reason,
            ]);

            if ($ticket->status === 'submitted' || $ticket->status === 'under_review' || $ticket->status === 'new') {
                $ticket->update(['status' => 'assigned']);
                $ticket->statusHistories()->create(['from_status' => 'submitted', 'to_status' => 'assigned', 'actor_id' => $actor->id]);
            }

            $this->audit->log('ticket.assigned', $ticket, old: $old, new: $ticket->only(array_keys($old)));

            $agent?->notify(new TicketNotification(
                "Ticket {$ticket->ticket_number} assigned to you",
                "{$ticket->subject} ({$ticket->priority})",
                route('ticketing.show', $ticket)
            ));

            return $ticket;
        });
    }

    /** Least-loaded agent of a team (open ticket count). */
    public function leastLoadedAgent(TicketTeam $team): ?User
    {
        return $team->members()->with('user')->get()
            ->map(fn ($e) => $e->user)->filter(fn ($u) => $u && $u->isActive())
            ->sortBy(fn ($u) => Ticket::where('assigned_agent_id', $u->id)->whereNotIn('status', ['resolved', 'closed'])->count())
            ->first();
    }
}
