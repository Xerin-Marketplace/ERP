<?php

namespace Modules\Ticketing\Services;

use Carbon\Carbon;
use Modules\Ticketing\Models\Ticket;
use Modules\Ticketing\Models\TicketSlaPolicy;
use Modules\Ticketing\Models\TicketSlaTracking;
use Modules\Ticketing\Notifications\TicketNotification;

/**
 * SLA: most-specific policy wins (type+company > type > company > default).
 * Due times are computed inside business hours.
 */
class SlaService
{
    public function policyFor(Ticket $ticket): ?TicketSlaPolicy
    {
        $base = TicketSlaPolicy::active()
            ->where(fn ($q) => $q->where('priority', $ticket->priority)->orWhereNull('priority'));

        $candidates = $base->get();

        return $candidates->sortByDesc(fn ($p) =>
            (($p->ticket_type_id === $ticket->ticket_type_id && $p->ticket_type_id) ? 8 : 0)
            + (($p->company_id === $ticket->requester_company_id && $p->company_id) ? 4 : 0)
            + (($p->department_id === $ticket->requester_department_id && $p->department_id) ? 2 : 0)
            + ($p->priority === $ticket->priority ? 1 : 0)
        )->first(fn ($p) =>
            (! $p->ticket_type_id || $p->ticket_type_id === $ticket->ticket_type_id)
            && (! $p->company_id || $p->company_id === $ticket->requester_company_id)
            && (! $p->department_id || $p->department_id === $ticket->requester_department_id)
        );
    }

    public function attach(Ticket $ticket): ?TicketSlaTracking
    {
        $policy = $this->policyFor($ticket);
        if (! $policy) return null;

        $start = $ticket->created_at ?? now();

        return $ticket->sla()->create([
            'policy_id' => $policy->id,
            'response_due_at' => $this->addBusinessMinutes($start, $policy->response_minutes, $policy),
            'resolution_due_at' => $this->addBusinessMinutes($start, $policy->resolution_minutes, $policy),
        ]);
    }

    /** Add N business minutes inside the policy's working window. */
    public function addBusinessMinutes(Carbon $from, int $minutes, TicketSlaPolicy $policy): Carbon
    {
        $cursor = $from->copy();
        $days = $policy->business_days ?? [1, 2, 3, 4, 5, 6, 7];

        while ($minutes > 0) {
            if (! in_array((int) $cursor->isoWeekday(), $days, true)) {
                $cursor = $cursor->addDay()->startOfDay()->setTime($policy->business_hours_start, 0);
                continue;
            }
            $dayStart = $cursor->copy()->setTime($policy->business_hours_start, 0);
            $dayEnd = $cursor->copy()->setTime($policy->business_hours_end, 0);

            if ($cursor->lt($dayStart)) { $cursor = $dayStart; continue; }
            if ($cursor->gte($dayEnd)) { $cursor = $dayEnd->addDay()->startOfDay()->setTime($policy->business_hours_start, 0); continue; }

            $avail = $cursor->diffInMinutes($dayEnd);
            if ($avail >= $minutes) {
                $cursor->addMinutes($minutes);
                $minutes = 0;
            } else {
                $minutes -= $avail;
                $cursor = $dayEnd;
            }
        }

        return $cursor;
    }

    /** Pause/unpause resolution timer on pending status. */
    public function onStatusChange(Ticket $ticket, string $to): void
    {
        $sla = $ticket->sla;
        if (! $sla) return;

        if ($to === 'resolved') {
            return; // resolution_at is on the ticket row
        }

        if (! $sla->policy->pauses_on_pending) return;

        if ($to === 'pending' && ! $sla->paused_at) {
            $sla->update(['paused_at' => now()]);
        } elseif ($to !== 'pending' && $sla->paused_at) {
            $paused = $sla->paused_at->diffInMinutes(now());
            $sla->update([
                'paused_minutes' => $sla->paused_minutes + $paused,
                'paused_at' => null,
                'resolution_due_at' => $sla->resolution_due_at->addMinutes($paused),
                'response_due_at' => $sla->response_due_at->addMinutes($paused),
            ]);
        }
    }

    public function markResponded(Ticket $ticket): void
    {
        $ticket->sla?->update(['response_at' => now()]);
    }

    /**
     * Scheduled scan: warn near-due, mark breaches, fire escalation rules.
     * Returns counts for logging/tests.
     */
    public function scan(): array
    {
        $stats = ['warned' => 0, 'breached' => 0, 'auto_closed' => 0, 'escalated' => 0];
        $escalations = app(EscalationService::class);

        TicketSlaTracking::whereHas('ticket', fn ($q) => $q->whereNotIn('status', ['resolved', 'closed']))
            ->with('ticket.requester', 'policy')
            ->chunk(100, function ($rows) use (&$stats, $escalations) {
                foreach ($rows as $sla) {
                    $ticket = $sla->ticket;
                    $policy = $sla->policy;
                    $warnAt = fn ($due, $target) => $due->copy()->subMinutes((int) ceil($target * (100 - $policy->warn_percent) / 100));

                    // Response warning + breach
                    if (! $sla->response_at) {
                        if (! $sla->response_warned && now()->gte($warnAt($sla->response_due_at, $policy->response_minutes)) && now()->lt($sla->response_due_at)) {
                            $sla->update(['response_warned' => true]);
                            $stats['warned']++;
                            $ticket->agent?->notify(new TicketNotification("SLA warning: {$ticket->ticket_number}", 'First response is approaching its SLA target.', route('ticketing.show', $ticket)));
                        }
                        if (! $sla->response_breached && now()->gt($sla->response_due_at)) {
                            $sla->update(['response_breached' => true]);
                            $stats['breached']++;
                            $stats['escalated'] += $escalations->fire('sla_breach', $ticket, ['timer' => 'response']);
                        }
                    }

                    // Resolution warning + breach
                    if (! $sla->resolution_warned && now()->gte($warnAt($sla->resolution_due_at, $policy->resolution_minutes)) && now()->lt($sla->resolution_due_at)) {
                        $sla->update(['resolution_warned' => true]);
                        $stats['warned']++;
                    }
                    if (! $sla->resolution_breached && now()->gt($sla->resolution_due_at)) {
                        $sla->update(['resolution_breached' => true]);
                        $stats['breached']++;
                        $stats['escalated'] += $escalations->fire('sla_breach', $ticket, ['timer' => 'resolution']);
                    }
                }
            });

        // Unassigned escalation
        Ticket::whereNotIn('status', ['resolved', 'closed'])
            ->whereNull('assigned_agent_id')->whereNull('assigned_team_id')
            ->where('created_at', '<=', now()->subHours(1))
            ->chunk(100, fn ($rows) => $rows->each(fn ($t) => $stats['escalated'] += $escalations->fire('unassigned', $t)));

        // Auto-close resolved tickets past the configured grace period
        $stats['auto_closed'] = app(AutomationService::class)->autoClose();

        return $stats;
    }
}
