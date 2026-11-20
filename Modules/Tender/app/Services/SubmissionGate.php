<?php

namespace Modules\Tender\Services;

use Modules\Tender\Models\Tender;

/**
 * Submission readiness checklist — a tender is SUBMITTED only after
 * verified evidence is recorded; never from a document upload alone.
 */
class SubmissionGate
{
    /** @return array{item:string, ok:bool, detail:string}[] */
    public function checklist(Tender $t): array
    {
        $items = [];
        $items[] = ['item' => 'Bid/No-Bid approved', 'ok' => in_array($t->assessment?->decision, ['bid','conditional_bid']), 'detail' => $t->assessment?->decision ?? 'none'];
        $mand = $t->requirements()->where('mandatory', true);
        $nonc = (clone $mand)->where('status', 'non_compliant')->count();
        $items[] = ['item' => 'Mandatory requirements compliant', 'ok' => $nonc === 0, 'detail' => "{$nonc} non-compliant"];
        $boq = $t->activeBoq;
        $items[] = ['item' => 'BOQ exists', 'ok' => (bool) $boq && $boq->items()->count() > 0, 'detail' => $boq ? $boq->items()->count().' items' : 'none'];
        if ($t->security_required && bccomp((string)$t->security_required,'0',2) > 0) {
            $sec = $t->securities()->where('type','tender_security')->whereIn('status',['issued','verified'])->first();
            $valid = $sec && $sec->expires_at >= ($sec->required_until ?? $sec->expires_at);
            $items[] = ['item' => 'Tender security valid', 'ok' => (bool) $sec && $valid, 'detail' => $sec ? "expires {$sec->expires_at}" : 'missing'];
        }
        $pending = $t->approvals()->where('decision','pending')->count();
        $approved = $t->approvals()->where('decision','approved')->count();
        $items[] = ['item' => 'All approvals granted', 'ok' => $pending === 0 && $approved > 0, 'detail' => "{$approved} approved, {$pending} pending"];
        $items[] = ['item' => 'Deadline not passed', 'ok' => ! $t->submission_deadline || $t->submission_deadline > now(), 'detail' => (string) $t->submission_deadline];
        return $items;
    }

    public function ready(Tender $t): bool
    {
        return collect($this->checklist($t))->every('ok');
    }
}
