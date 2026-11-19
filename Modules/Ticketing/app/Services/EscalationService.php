<?php

namespace Modules\Ticketing\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Modules\Ticketing\Models\Ticket;
use Modules\Ticketing\Models\TicketEscalationRule;
use Modules\Ticketing\Notifications\TicketNotification;

class EscalationService
{
    public function __construct(private AuditService $audit) {}

    /**
     * Fire all matching active rules for a trigger. Returns number fired.
     * Each rule fires at most once per ticket (history check = loop guard).
     */
    public function fire(string $trigger, Ticket $ticket, array $context = []): int
    {
        $fired = 0;

        TicketEscalationRule::active()->where('trigger', $trigger)->get()
            ->each(function ($rule) use ($ticket, $trigger, $context, &$fired) {
                if ($rule->priority && $rule->priority !== $ticket->priority) return;
                if ($ticket->escalations()->where('rule_id', $rule->id)->exists()) return; // once per rule
                if ($trigger === 'unassigned' && $rule->after_minutes
                    && $ticket->created_at->gt(now()->subMinutes($rule->after_minutes))) return;

                DB::transaction(function () use ($rule, $ticket, $trigger, $context, &$fired) {
                    $ticket->escalations()->create(['rule_id' => $rule->id, 'trigger' => $trigger, 'details' => $context]);

                    if ($rule->new_priority) {
                        $ticket->update(['priority' => $rule->new_priority]);
                    }
                    if ($rule->to_team_id || $rule->to_agent_id) {
                        app(AssignmentService::class)->assign($ticket,
                            array_filter(['team_id' => $rule->to_team_id, 'agent_id' => $rule->to_agent_id]),
                            $rule->notify_user_id ? \App\Models\User::find($rule->notify_user_id) : $ticket->requester,
                            "Escalation: {$rule->name}");
                    }

                    \App\Models\User::whereKey($rule->notify_user_id)->first()
                        ?->notify(new TicketNotification("Escalation: {$ticket->ticket_number}", "{$rule->name} fired on '{$ticket->subject}'.", route('ticketing.show', $ticket)));

                    $this->audit->log('ticket.escalated', $ticket, new: ['rule' => $rule->name, 'trigger' => $trigger]);
                    $fired++;
                });
            });

        return $fired;
    }
}
