<?php

namespace Modules\Tender\Services;

use Modules\Tender\Models\{Tender, TenderQualification};

/**
 * Tender Intelligence & Profitability Control — pre-bid capability
 * snapshot: compliance readiness, expired docs, capital need, margin.
 */
class TenderIntelligence
{
    public function analyze(Tender $t): array
    {
        $reqs = $t->requirements;
        $mand = $reqs->where('mandatory', true);
        $expired = TenderQualification::where('company_id', $t->company_id)->where('status', 'expired')->get();
        $boq = $t->activeBoq;
        $totals = $boq ? app(BoqService::class)->totals($boq) : null;
        $sec = $t->securities()->where('type','tender_security')->latest()->first();

        return [
            'assessment_score' => $t->assessment?->total_score,
            'decision' => $t->assessment?->decision ?? 'not_assessed',
            'requirements' => [
                'total' => $reqs->count(), 'mandatory' => $mand->count(),
                'compliant' => $mand->where('status','compliant')->count(),
                'non_compliant' => $mand->where('status','non_compliant')->count(),
                'pending' => $mand->whereIn('status',['not_reviewed','clarification'])->count(),
            ],
            'expired_qualifications' => $expired->count(),
            'boq' => $totals ? [
                'direct_cost' => $totals['direct'], 'grand_total' => $totals['grand_total'],
                'gross_profit' => $totals['profit'], 'margin_pct' => $totals['margin_pct'],
            ] : null,
            'security' => $sec ? [
                'amount' => (string) $sec->amount,
                'validity' => app(SecurityValidityService::class)->check($sec),
            ] : null,
            'capital_required' => $totals ? bcadd($totals['direct'], (string) ($sec?->amount ?? 0), 2) : (string) ($t->security_required ?? 0),
            'submission_readiness' => app(SubmissionGate::class)->ready($t),
        ];
    }
}
