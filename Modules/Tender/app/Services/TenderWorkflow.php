<?php

namespace Modules\Tender\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Modules\Tender\Models\Tender;
use Modules\Tender\Models\TenderTransition;

/** Stage transitions with hard gates — API cannot skip required stages. */
class TenderWorkflow
{
    /** stage => allowed next stages + optional guard */
    private const FLOW = [
        'registered' => ['initial_review', 'cancelled'],
        'initial_review' => ['bid_decision', 'cancelled'],
        'bid_decision' => ['preparation', 'cancelled'],
        'preparation' => ['boq', 'cancelled'],
        'boq' => ['costing', 'cancelled'],
        'costing' => ['technical_proposal', 'cancelled'],
        'technical_proposal' => ['financial_proposal', 'cancelled'],
        'financial_proposal' => ['internal_review', 'cancelled'],
        'internal_review' => ['approval', 'cancelled'],
        'approval' => ['submission_ready', 'cancelled'],
        'submission_ready' => ['submitted', 'cancelled'],
        'submitted' => ['evaluation', 'cancelled'],
        'evaluation' => ['clarification', 'negotiation', 'awarded', 'unsuccessful', 'cancelled'],
        'clarification' => ['evaluation', 'negotiation', 'awarded', 'unsuccessful'],
        'negotiation' => ['awarded', 'unsuccessful'],
        'awarded' => ['contract_signing'],
        'contract_signing' => ['contract_active'],
        'contract_active' => ['execution'],
        'execution' => ['completed'],
        'completed' => ['closed'],
        'unsuccessful' => ['closed'],
        'cancelled' => ['closed'],
    ];

    public function can(Tender $t, string $to): bool
    {
        return in_array($to, self::FLOW[$t->stage] ?? [], true);
    }

    public function transition(Tender $t, string $to, User $actor, ?string $note = null, bool $internal = false): Tender
    {
        if (! $this->can($t, $to)) {
            throw ValidationException::withMessages([
                'stage' => "Cannot move from '{$t->stage}' to '{$to}'."]);
        }
        if (! $internal) $this->guard($t, $to);
        $from = $t->stage;
        $t->update(['stage' => $to]);
        TenderTransition::create(['tender_id' => $t->id, 'from_stage' => $from,
            'to_stage' => $to, 'actor_id' => $actor->id, 'note' => $note]);
        return $t;
    }

    /** Hard prerequisites for gated stages. */
    private function guard(Tender $t, string $to): void
    {
        $fail = fn (string $msg) => throw ValidationException::withMessages(['stage' => $msg]);
        match ($to) {
            'preparation' => in_array($t->assessment?->decision, ['bid', 'conditional_bid'])
                ?: $fail('Bid/No-Bid decision required first.'),
            'submission_ready' => $this->allApproved($t)
                ?: $fail('All approval steps must be approved before submission readiness.'),
            'submitted' => $fail('Use the submission endpoint — verified evidence is required.'),
            'contract_signing' => $t->contracts()->exists()
                ?: $fail('A contract record must exist first.'),
            default => true,
        };
    }

    private function allApproved(Tender $t): bool
    {
        $pending = $t->approvals()->where('decision', 'pending')->count();
        $approved = $t->approvals()->where('decision', 'approved')->count();
        return $pending === 0 && $approved > 0;
    }
}
