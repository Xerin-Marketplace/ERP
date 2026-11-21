<?php

namespace Modules\Ticketing\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Modules\Ticketing\Models\Ticket;
use Modules\Ticketing\Models\TicketAutomationRule;
use Modules\Ticketing\Notifications\TicketNotification;

/**
 * Rule engine: conditions match → action executes → logged.
 * Loop guard: max_runs_per_ticket enforced via automation log count.
 */
class AutomationService
{
    public function __construct(private AuditService $audit) {}

    public function run(string $event, Ticket $ticket): void
    {
        TicketAutomationRule::active()->where('event', $event)->get()
            ->each(function ($rule) use ($ticket) {
                if ($ticket->automationLogs()->where('rule_id', $rule->id)->count() >= $rule->max_runs_per_ticket) return;
                if (! $this->matches($rule, $ticket)) { $this->log($rule, $ticket, 'skipped', 'conditions not met'); return; }

                try {
                    $this->execute($rule, $ticket);
                    $this->log($rule, $ticket, 'success');
                } catch (\Throwable $e) {
                    $this->log($rule, $ticket, 'failed', $e->getMessage());
                }
            });
    }

    public function matches(TicketAutomationRule $rule, Ticket $ticket): bool
    {
        $c = $rule->conditions ?? [];
        foreach (['priority', 'category_id', 'service_id', 'ticket_type_id'] as $key) {
            if (isset($c[$key]) && (string) $ticket->{$key} !== (string) $c[$key]) return false;
        }
        return true;
    }

    private function execute(TicketAutomationRule $rule, Ticket $ticket): void
    {
        $p = $rule->action_params ?? [];

        match ($rule->action) {
            'assign_team' => app(AssignmentService::class)->assign($ticket, ['team_id' => $p['team_id'] ?? null], $ticket->requester, "Automation: {$rule->name}"),
            'assign_category_default' => $this->assignCategoryDefault($ticket),
            'set_priority' => $ticket->update(['priority' => $p['priority'] ?? $ticket->priority]),
            'notify_managers' => $this->notifyManagers($ticket, $rule),
            'auto_close' => null, // handled by autoClose() scheduler path
            default => null,
        };
    }

    private function assignCategoryDefault(Ticket $ticket): void
    {
        if ($teamId = $ticket->category?->default_team_id) {
            app(AssignmentService::class)->assign($ticket, ['team_id' => $teamId], $ticket->requester, 'Automation: category default team');
        }
    }

    private function notifyManagers(Ticket $ticket, TicketAutomationRule $rule): void
    {
        $managers = User::role(['CTO and Admin', 'super-admin'])->get();
        $specific = isset($rule->action_params['user_id']) ? User::whereKey($rule->action_params['user_id'])->first() : null;
        $managers->push($specific)->filter()->unique('id')->each(fn ($u) =>
            $u->notify(new TicketNotification("{$ticket->priority} ticket {$ticket->ticket_number}", $ticket->subject, route('ticketing.show', $ticket))));
    }

    /** Scheduled: close resolved tickets after configured grace (per rule). */
    public function autoClose(): int
    {
        $closed = 0;
        TicketAutomationRule::active()->where('event', 'scheduled')->where('action', 'auto_close')->get()
            ->each(function ($rule) use (&$closed) {
                $hours = (int) ($rule->action_params['after_hours'] ?? 72);
                Ticket::where('status', 'resolved')
                    ->where('resolved_at', '<=', now()->subHours($hours))
                    ->each(function ($ticket) use ($rule, &$closed) {
                        if ($ticket->automationLogs()->where('rule_id', $rule->id)->count() >= $rule->max_runs_per_ticket) return;
                        $ticket->update(['status' => 'closed', 'closed_at' => now()]);
                        $ticket->statusHistories()->create(['from_status' => 'resolved', 'to_status' => 'closed', 'note' => "Auto-closed by rule '{$rule->name}'"]);
                        $this->log($rule, $ticket, 'success');
                        $this->audit->log('ticket.auto_closed', $ticket);
                        $closed++;
                    });
            });
        return $closed;
    }

    private function log(TicketAutomationRule $rule, Ticket $ticket, string $result, ?string $message = null): void
    {
        $ticket->automationLogs()->create(['rule_id' => $rule->id, 'action' => $rule->action, 'result' => $result, 'message' => $message]);
    }
}
