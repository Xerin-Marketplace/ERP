<?php

namespace Modules\Ticketing\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Ticketing\Models\Ticket;
use Modules\Ticketing\Models\TicketApproval;
use Modules\Ticketing\Notifications\TicketNotification;

/**
 * Multi-step approvals. Chain comes from service/type config — never
 * hardcoded: ["manager","it","finance","department_head"].
 */
class ApprovalService
{
    public function __construct(private AuditService $audit) {}

    public function open(Ticket $ticket, array $chain): void
    {
        DB::transaction(function () use ($ticket, $chain) {
            $ticket->approvals()->delete();
            foreach (array_values($chain) as $i => $role) {
                $ticket->approvals()->create([
                    'step' => $i + 1,
                    'approver_role' => $role,
                    'approver_id' => $this->resolveApprover($ticket, $role),
                    'status' => 'pending',
                ]);
            }
            $ticket->approvals()->pending()->get()->each(fn ($a) => $a->approver?->notify(new TicketNotification(
                "Approval required: {$ticket->ticket_number}", $ticket->subject, route('ticketing.approvals.index'))));

            $this->audit->log('ticket.approvals_opened', $ticket, new: ['chain' => $chain]);
        });
    }

    /** Who may decide this step. */
    public function canDecide(User $user, TicketApproval $approval): bool
    {
        if ($approval->approver_id && $approval->approver_id === $user->id) return true;
        if ($approval->approver_id) return false; // bound to a specific person
        return match ($approval->approver_role) {
            'manager' => $approval->ticket->requesterEmployee?->manager?->user_id === $user->id
                || $user->can('ticket.approve'),
            default => $user->can('ticket.approve'),
        };
    }

    public function decide(TicketApproval $approval, User $user, string $decision, ?string $note = null): TicketApproval
    {
        if (! $this->canDecide($user, $approval)) {
            throw ValidationException::withMessages(['approval' => 'You are not authorized to decide this step.']);
        }
        if ($decision === 'rejected' && ! $note) {
            throw ValidationException::withMessages(['note' => 'Rejection requires a reason.']);
        }

        return DB::transaction(function () use ($approval, $user, $decision, $note) {
            $approval = TicketApproval::lockForUpdate()->findOrFail($approval->id);
            abort_if($approval->status !== 'pending', 422, 'Step already decided.');

            $approval->update(['status' => $decision, 'decided_by' => $user->id, 'decision_note' => $note, 'decided_at' => now()]);

            $ticket = $approval->ticket;
            $ticket->comments()->create([
                'author_id' => $user->id,
                'body' => "Approval step {$approval->step} ({$approval->approver_role}) {$decision}" . ($note ? ": {$note}" : ''),
                'kind' => 'system', 'is_internal' => false,
            ]);

            if ($decision === 'rejected') {
                $ticket->approvals()->pending()->update(['status' => 'skipped']);
                $ticket->requester->notify(new TicketNotification("Request rejected: {$ticket->ticket_number}", $note ?? '', route('ticketing.show', $ticket)));
            } elseif (! $ticket->approvals()->pending()->exists()) {
                $ticket->requester->notify(new TicketNotification("All approvals complete: {$ticket->ticket_number}", 'Your request is fully approved.', route('ticketing.show', $ticket)));
            }

            $this->audit->log("ticket.approval_{$decision}", $approval, metadata: ['ticket_id' => $ticket->id, 'step' => $approval->step]);

            return $approval;
        });
    }

    private function resolveApprover(Ticket $ticket, string $role): ?int
    {
        return match ($role) {
            'manager' => $ticket->requesterEmployee?->manager?->user_id,
            'department_head' => \Modules\Organization\Models\Department::find($ticket->requester_department_id)?->head_user_id
                ?? null,
            default => null, // role-gated pool: any user with ticket.approve
        };
    }
}
