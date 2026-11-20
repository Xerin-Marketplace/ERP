<?php

namespace Modules\Tender\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Modules\Tender\Models\Tender;
use Modules\Tender\Models\TenderApproval;
use Modules\Tender\Models\TenderAssessment;

/**
 * Tender Intelligence — weighted scoring → bid/no-bid.
 * Thresholds are constants here but intended configurable via SettingRegistry.
 */
class BidNoBidService
{
    private const WEIGHTS = ['commercial' => 30, 'technical' => 25, 'compliance' => 20,
        'strategic' => 15, 'risk' => 10]; // risk scored inverted (100 = low risk)
    private const BID_THRESHOLD = 65;

    public function assess(Tender $t, array $scores, User $actor): TenderAssessment
    {
        foreach (self::WEIGHTS as $k => $w) {
            if (! isset($scores[$k])) {
                throw ValidationException::withMessages([$k => "Score '{$k}' is required."]);
            }
        }
        $total = 0;
        foreach (self::WEIGHTS as $k => $w) {
            $s = max(0, min(100, (int) $scores[$k]['score']));
            $scores[$k]['score'] = $s;
            $total += $s * $w / 100;
        }
        $recommendation = $total >= self::BID_THRESHOLD ? 'bid' : 'no_bid';

        return TenderAssessment::updateOrCreate(['tender_id' => $t->id], [
            'scores' => $scores, 'total_score' => (int) round($total),
            'decision' => 'pending_review', 'reason' => "Recommendation: {$recommendation}",
            'assessed_by' => $actor->id,
        ]);
    }

    public function decide(Tender $t, string $decision, User $actor, ?string $reason = null): TenderAssessment
    {
        abort_unless(in_array($decision, ['bid', 'no_bid', 'conditional_bid']), 422);
        $a = $t->assessment;
        if (! $a) {
            throw ValidationException::withMessages(['assessment' => 'Complete the assessment first.']);
        }
        if ($decision === 'no_bid' && blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A reason is required for NO_BID.']);
        }
        $a->update(['decision' => $decision, 'reason' => $reason ?? $a->reason,
            'decided_by' => $actor->id, 'decided_at' => now()]);
        TenderApproval::create(['tender_id' => $t->id, 'step' => 'bid_decision',
            'approver_id' => $actor->id, 'decision' => 'approved', 'comment' => $reason,
            'decided_at' => now()]);
        $wf = app(TenderWorkflow::class);
        if ($decision !== 'no_bid') {
            if ($wf->can($t, 'bid_decision')) {
                $t = $wf->transition($t, 'bid_decision', $actor);
            }
            $wf->transition($t->fresh(), 'preparation', $actor, "Bid decision: {$decision}");
        } else {
            $t->update(['stage' => 'closed']);
            \Modules\Tender\Models\TenderTransition::create(['tender_id' => $t->id,
                'from_stage' => 'bid_decision', 'to_stage' => 'closed', 'actor_id' => $actor->id,
                'note' => "NO_BID: {$reason}"]);
        }
        return $a;
    }
}
