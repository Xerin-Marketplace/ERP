<?php
namespace Modules\Sales\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Commercial\Models\Customer;
use Modules\Commercial\Models\Lead;
use Modules\Commercial\Models\Quotation;
use Modules\Commercial\Models\RateCard;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Sales\Models\CommercialCampaign;
use Modules\Sales\Models\CommercialContract;
use Modules\Sales\Models\CommercialPartner;
use Modules\Sales\Models\CommissionRecord;
use Modules\Sales\Models\CommissionRule;
use Modules\Sales\Models\ContractVersion;
use Modules\Sales\Models\CustomerAcquisition;
use Modules\Sales\Models\CustomerSegment;
use Modules\Sales\Models\PipelineStage;
use Modules\Sales\Models\PricingApproval;
use Modules\Sales\Models\SalesOpportunity;
use Modules\Sales\Models\SalesTarget;

class SalesService
{
    private string $seq = '';
    private function ref(string $prefix): string
    {
        if ($this->seq === $prefix) $this->i = ($this->i ?? 0) + 1; else { $this->seq = $prefix; $this->i = 1; }
        return sprintf('%s%s-%06d', $prefix, now()->format('Y'), $this->i);
    }
    private int $i = 0;

    public function dashboard(?int $branchId = null): array
    {
        $invoices = CustomerInvoice::query();
        if ($branchId) $invoices->whereHas('customer', fn($q) => $q->where('branch_id', $branchId));
        $opps = SalesOpportunity::forBranch($branchId);
        return [
            'customers'        => Customer::when($branchId, fn($q) => $q->where('branch_id', $branchId))->count(),
            'leads_active'     => Lead::whereNotIn('stage', ['converted', 'lost'])->count(),
            'opportunities'    => (clone $opps)->where('status', 'open')->count(),
            'opportunities_won'=> (clone $opps)->where('status', 'won')->count(),
            'quotations'       => Quotation::when($branchId, fn($q) => $q->where('branch_id', $branchId))->count(),
            'contracts_active' => CommercialContract::where('status', 'active')
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))->count(),
            'contracts_expiring' => CommercialContract::where('status', 'active')
                ->whereBetween('expiry_date', [now(), now()->addDays(30)])->count(),
            'partners_active'  => CommercialPartner::where('status', 'active')->count(),
            'commissions_pending' => CommissionRecord::whereIn('status', ['calculated', 'under_review'])->sum('commission_amount'),
            'revenue_invoiced' => (clone $invoices)->sum('total'),
            'revenue_collected'=> (clone $invoices)->sum('received_amount'),
        ];
    }

    // ── Opportunities ───────────────────────────────────────────
    public function createOpportunity(array $data): SalesOpportunity
    {
        $stageId = $data['stage_id'] ?? PipelineStage::active()->first()?->id;
        return SalesOpportunity::create(array_merge($data, [
            'reference' => $this->ref('XER-OPP-'), 'stage_id' => $stageId, 'status' => 'open',
            'probability' => PipelineStage::find($stageId)?->probability ?? 10,
        ]));
    }

    public function moveOpportunity(SalesOpportunity $o, int $stageId): SalesOpportunity
    {
        if ($o->status !== 'open' && $o->status !== 'on_hold')
            throw new InvalidArgumentException("Cannot move {$o->status} opportunity {$o->reference}");
        $stage = PipelineStage::active()->findOrFail($stageId);
        $o->update(['stage_id' => $stage->id, 'probability' => $stage->probability]);
        return $o;
    }

    public function closeOpportunity(SalesOpportunity $o, string $status, ?string $reason = null): SalesOpportunity
    {
        if (!in_array($status, ['won', 'lost'], true)) throw new InvalidArgumentException('Status must be won|lost');
        if ($status === 'lost' && !$reason) throw new InvalidArgumentException('Lost reason required');
        $o->update(['status' => $status, 'lost_reason' => $reason]);
        return $o;
    }

    public function weightedPipeline(?int $branchId = null): float
    {
        return (float) SalesOpportunity::forBranch($branchId)->where('status', 'open')
            ->selectRaw('COALESCE(SUM(expected_revenue * probability / 100), 0) as w')->value('w');
    }

    public function forecast(?int $branchId = null): array
    {
        $open = SalesOpportunity::forBranch($branchId)->where('status', 'open')
            ->whereNotNull('expected_close')->get();
        return $open->groupBy(fn($o) => $o->expected_close->format('Y-m'))
            ->map(fn($g, $m) => ['month' => $m, 'weighted' => round($g->sum(fn($o) => $o->expected_revenue * $o->probability / 100), 2)])
            ->values()->all();
    }

    // ── Targets ─────────────────────────────────────────────────
    public function setTarget(array $data): SalesTarget
    {
        return SalesTarget::updateOrCreate(
            ['scope' => $data['scope'], 'scope_id' => $data['scope_id'] ?? null,
             'period_type' => $data['period_type'], 'period' => $data['period'], 'metric' => $data['metric']],
            ['target' => $data['target'], 'currency' => $data['currency'] ?? 'TZS']);
    }

    public function targetAchievement(SalesTarget $t): array
    {
        [$from, $to] = $this->periodRange($t->period_type, $t->period);
        $actual = match ($t->metric) {
            'revenue'       => (float) CustomerInvoice::whereBetween('invoice_date', [$from, $to])
                ->when($t->scope === 'branch', fn($q) => $q->whereHas('customer', fn($c) => $c->where('branch_id', $t->scope_id)))
                ->sum('received_amount'), // actual = collected, not quoted
            'new_customers' => (float) Customer::whereBetween('created_at', [$from, $to])
                ->when($t->scope === 'branch', fn($q) => $q->where('branch_id', $t->scope_id))->count(),
            'contracts'     => (float) CommercialContract::whereIn('status', ['active', 'renewed'])
                ->whereBetween('start_date', [$from, $to])->count(),
            'shipments'     => (float) \Modules\Shipment\Models\Shipment::whereBetween('created_at', [$from, $to])->count(),
            default         => 0.0,
        };
        return ['target' => (float) $t->target, 'actual' => $actual,
            'variance' => $actual - (float) $t->target,
            'pct' => $t->target > 0 ? round($actual / $t->target * 100, 1) : null];
    }

    private function periodRange(string $type, string $period): array
    {
        if ($type === 'monthly') { $d = Carbon::createFromFormat('Y-m', $period); return [$d->copy()->startOfMonth(), $d->copy()->endOfMonth()]; }
        if ($type === 'annual')  { $d = Carbon::createFromFormat('Y', $period);   return [$d->copy()->startOfYear(), $d->copy()->endOfYear()]; }
        [$y, $q] = explode('-Q', $period);
        $d = Carbon::create($y, ($q - 1) * 3 + 1, 1);
        return [$d->copy()->startOfMonth(), $d->copy()->addMonths(2)->endOfMonth()];
    }

    // ── Contracts ───────────────────────────────────────────────
    public function createContract(array $data, int $actor): CommercialContract
    {
        return DB::transaction(function () use ($data, $actor) {
            $c = CommercialContract::create(array_merge($data, ['reference' => $this->ref('XER-CON-'), 'version' => 1, 'status' => 'draft']));
            $c->versions()->create(['version' => 1, 'terms' => $c->terms ?? [], 'status' => 'current', 'created_by' => $actor]);
            return $c;
        });
    }

    public function amendContract(CommercialContract $c, array $newTerms, int $actor): ContractVersion
    {
        if (!in_array($c->status, ['draft', 'under_review', 'active'], true))
            throw new InvalidArgumentException("Cannot amend contract in {$c->status}");
        return DB::transaction(function () use ($c, $newTerms, $actor) {
            $c->versions()->where('status', 'current')->update(['status' => 'superseded']);
            $v = $c->versions()->create(['version' => $c->version + 1, 'terms' => $newTerms, 'status' => 'current', 'created_by' => $actor]);
            $c->update(['version' => $v->version, 'terms' => $newTerms]);
            return $v;
        });
    }

    public function renewContract(CommercialContract $c, array $data, int $actor): CommercialContract
    {
        if (!in_array($c->status, ['active', 'expired'], true))
            throw new InvalidArgumentException('Only active/expired contracts can be renewed');
        return DB::transaction(function () use ($c, $data, $actor) {
            $c->update(['status' => 'renewed']);
            return $this->createContract(array_merge([
                'type' => $c->type, 'customer_id' => $c->customer_id, 'partner_id' => $c->partner_id,
                'branch_id' => $c->branch_id, 'owner_id' => $c->owner_id, 'terms' => $c->terms,
            ], $data), $actor);
        });
    }

    // ── Partners (agents/brokers) ───────────────────────────────
    public function registerPartner(array $data): CommercialPartner
    {
        $prefix = $data['type'] === 'broker' ? 'XER-BKR-' : 'XER-AGT-';
        return CommercialPartner::create(array_merge($data, ['reference' => $this->ref($prefix), 'status' => 'registered']));
    }

    public function partnerPerformance(CommercialPartner $p): array
    {
        $acq = $p->acquisitions();
        return [
            'referrals'        => (clone $acq)->count(),
            'converted'        => (clone $acq)->whereNotNull('first_revenue_at')->count(),
            'commission_earned'=> (float) CommissionRecord::where('beneficiary_type', $p->type)
                ->where('beneficiary_id', $p->id)->whereIn('status', ['approved', 'payable', 'paid'])->sum('commission_amount'),
            'commission_paid'  => (float) CommissionRecord::where('beneficiary_type', $p->type)
                ->where('beneficiary_id', $p->id)->where('status', 'paid')->sum('commission_amount'),
        ];
    }

    // ── Commissions ─────────────────────────────────────────────
    public function generateCommission(CommissionRule $rule, string $sourceType, int $sourceId, float $basisAmount, ?float $collectedAmount = null): ?CommissionRecord
    {
        if (!$rule->isEffective()) return null;
        if ($rule->requires_collected && ($collectedAmount ?? 0) <= 0)
            return null; // never commission on uncollected revenue
        $basis = $rule->requires_collected ? ($collectedAmount ?? $basisAmount) : $basisAmount;
        $amount = match ($rule->basis) {
            'revenue_pct'     => round($basis * (float) $rule->rate, 2),
            'per_shipment', 'per_acquisition' => (float) $rule->fixed_amount,
            default           => (float) $rule->fixed_amount ?? 0,
        };
        if ($amount <= 0) return null;
        // unique(beneficiary, source) in DB prevents duplicates; catch gracefully
        try {
            return CommissionRecord::create([
                'reference' => $this->ref('XER-CMS-'), 'rule_id' => $rule->id,
                'beneficiary_type' => $rule->beneficiary_type, 'beneficiary_id' => $rule->beneficiary_id,
                'source_type' => $sourceType, 'source_id' => $sourceId,
                'basis_amount' => $basis, 'commission_amount' => $amount,
                'currency' => $rule->currency ?? 'TZS', 'status' => 'calculated',
                'calc_snapshot' => ['basis' => $rule->basis, 'rate' => $rule->rate, 'fixed' => $rule->fixed_amount, 'at' => now()->toISOString()],
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return CommissionRecord::where('beneficiary_type', $rule->beneficiary_type)
                ->where('beneficiary_id', $rule->beneficiary_id)
                ->where('source_type', $sourceType)->where('source_id', $sourceId)->first();
        }
    }

    public function settleCommission(CommissionRecord $r, string $financeRef): CommissionRecord
    {
        if ($r->status !== 'payable') throw new InvalidArgumentException("Commission {$r->reference} not payable ({$r->status})");
        if (empty($financeRef)) throw new InvalidArgumentException('Finance settlement reference required — commission cannot be marked paid without a payment record');
        $r->update(['status' => 'paid', 'settlement_ref' => $financeRef]);
        return $r;
    }

    // ── Pricing simulation ──────────────────────────────────────
    public function simulatePrice(array $params): array
    {
        // precedence: customer-specific rate card > branch/route rate card > standard
        $q = RateCard::where('service_type', $params['service_type'] ?? 'standard');
        if (!empty($params['origin']))      $q->where('origin_city', $params['origin']);
        if (!empty($params['destination'])) $q->where('destination_city', $params['destination']);
        $cards = $q->get();
        $card = $cards->firstWhere('customer_category', $params['customer_category'] ?? null)
            ?? $cards->first();
        if (!$card) return ['error' => 'no_rate_card', 'price' => null];

        $chargeable = max($params['gross_weight'] ?? 0, $params['volumetric_weight'] ?? 0);
        $base  = (float) $card->base_rate + ($chargeable * (float) $card->per_kg_rate);
        $price = max($base, (float) $card->minimum_charge);
        $discount = 0.0; $campaign = null;
        if (!empty($params['campaign_id']) && ($c = CommercialCampaign::find($params['campaign_id']))?->isUsable()) {
            $discount = $c->discountFor($price); $campaign = $c->reference;
        }
        return [
            'rate_card' => $card->code, 'chargeable_weight' => $chargeable,
            'base' => round($base, 2), 'minimum_applied' => $base < (float) $card->minimum_charge,
            'price' => round($price, 2), 'discount' => round($discount, 2),
            'final' => round($price - $discount, 2), 'currency' => $card->currency,
            'campaign' => $campaign,
            'explanation' => "card {$card->code}: {$card->base_rate} + {$chargeable}kg×{$card->per_kg_rate}, min {$card->minimum_charge}",
        ];
    }

    public function requestPricingApproval(array $data, int $actor): PricingApproval
    {
        return PricingApproval::create(array_merge($data, [
            'reference' => $this->ref('XER-PRA-'), 'requested_by' => $actor, 'status' => 'pending']));
    }

    public function decidePricing(PricingApproval $p, string $decision, int $actor): PricingApproval
    {
        if ($p->requested_by === $actor) throw new InvalidArgumentException('Requester cannot approve own pricing exception (separation of duties)');
        $p->decide($decision, $actor);
        return $p;
    }

    // ── Segmentation & acquisition ──────────────────────────────
    public function evaluateSegment(CustomerSegment $s, Customer $c): bool
    {
        $r = $s->rules ?? [];
        if (!empty($r['types']) && !in_array($c->type ?? 'individual', $r['types'], true)) return false;
        if (isset($r['min_revenue'])) {
            $rev = CustomerInvoice::where('customer_id', $c->id)->sum('received_amount');
            if ($rev < $r['min_revenue']) return false;
        }
        if (isset($r['min_shipments'])) {
            $n = \Modules\Cargo\Models\CargoPackage::where('customer_id', $c->id)->count();
            if ($n < $r['min_shipments']) return false;
        }
        return true;
    }

    public function attributeCustomer(Customer $c, array $data): CustomerAcquisition
    {
        return CustomerAcquisition::updateOrCreate(['customer_id' => $c->id], array_merge($data, ['registered_at' => $c->created_at]));
    }

    public function recordMilestone(Customer $c, string $milestone): void
    {
        $allowed = ['first_quotation_at', 'first_accepted_at', 'first_shipment_at', 'first_revenue_at'];
        if (!in_array($milestone, $allowed, true)) throw new InvalidArgumentException('Unknown milestone');
        $a = CustomerAcquisition::where('customer_id', $c->id)->first();
        if ($a && !$a->$milestone) $a->update([$milestone => now()]);
    }

    // ── Revenue analytics — Finance is source of truth ──────────
    public function revenueAnalytics(?int $branchId = null): array
    {
        $inv = CustomerInvoice::query();
        if ($branchId) $inv->whereHas('customer', fn($q) => $q->where('branch_id', $branchId));
        return [
            'pipeline_value'   => (float) SalesOpportunity::forBranch($branchId)->where('status', 'open')->sum('expected_revenue'),
            'weighted_pipeline'=> $this->weightedPipeline($branchId),
            'quoted_value'     => (float) Quotation::when($branchId, fn($q) => $q->where('branch_id', $branchId))->sum('total_price'),
            'invoiced'         => (float) (clone $inv)->sum('total'),
            'collected'        => (float) (clone $inv)->sum('received_amount'),
            'outstanding'      => (float) (clone $inv)->sum('total') - (float) (clone $inv)->sum('received_amount'),
            'commission_cost'  => (float) CommissionRecord::whereIn('status', ['approved','payable','paid'])->sum('commission_amount'),
        ];
    }
}
