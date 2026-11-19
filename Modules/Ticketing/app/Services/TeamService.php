<?php

namespace Modules\Ticketing\Services;

use App\Services\AuditService;
use Modules\Employee\Models\Employee;
use Modules\Ticketing\Models\Ticket;
use Modules\Ticketing\Models\TicketTask;
use Modules\Ticketing\Models\TicketTeam;

class TeamService
{
    public function __construct(private AuditService $audit) {}

    public function workload(TicketTeam $team): array
    {
        $openTickets = Ticket::where('assigned_team_id', $team->id)->whereNotIn('status', ['resolved', 'closed'])->count();
        $memberIds = $team->members()->pluck('employees.id');

        return [
            'open_tickets' => $openTickets,
            'open_tasks' => TicketTask::where('team_id', $team->id)->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'members' => $team->members()->with('personal', 'user')->get()->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->full_name,
                'open_tasks' => TicketTask::where('assignee_employee_id', $e->id)->whereNotIn('status', ['completed', 'cancelled'])->count(),
                'open_tickets' => $e->user ? Ticket::where('assigned_agent_id', $e->user->id)->whereNotIn('status', ['resolved', 'closed'])->count() : 0,
            ])->all(),
        ];
    }
}
