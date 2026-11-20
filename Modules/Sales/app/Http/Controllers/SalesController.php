<?php
namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Sales\Models\CommercialCampaign;
use Modules\Sales\Models\CommercialContract;
use Modules\Sales\Models\CommercialPartner;
use Modules\Sales\Models\CommissionRecord;
use Modules\Sales\Models\CommissionRule;
use Modules\Sales\Models\PipelineStage;
use Modules\Sales\Models\PricingApproval;
use Modules\Sales\Models\SalesOpportunity;
use Modules\Sales\Models\SalesTarget;
use Modules\Sales\Services\SalesService;

class SalesController extends Controller
{
    public function __construct(private SalesService $svc) {}
    private function bid() { return auth()->user()->employee?->branch_id; }

    public function dashboard()
    {
        abort_unless(auth()->user()->can('commercial.dashboard.view'), 403);
        return view('sales::dashboard', ['d' => $this->svc->dashboard($this->bid()),
            'analytics' => $this->svc->revenueAnalytics($this->bid()), 'forecast' => $this->svc->forecast($this->bid())]);
    }

    // Pipeline
    public function pipeline()
    {
        $stages = PipelineStage::active()->get();
        $opps = SalesOpportunity::forBranch($this->bid())->with(['lead','customer','stage','owner'])->get()->groupBy('stage_id');
        return view('sales::pipeline', compact('stages', 'opps'));
    }
    public function storeOpportunity(Request $r)
    {
        $this->svc->createOpportunity($r->validate([
            'name' => 'required', 'lead_id' => 'nullable|exists:leads,id', 'customer_id' => 'nullable|exists:customers,id',
            'expected_revenue' => 'nullable|numeric', 'expected_close' => 'nullable|date',
            'service_category' => 'nullable|string', 'competitor' => 'nullable|string',
        ]) + ['branch_id' => $this->bid(), 'owner_id' => auth()->id()]);
        return back()->with('success', 'Opportunity created');
    }
    public function moveOpportunity(SalesOpportunity $opportunity, Request $r)
    {
        $this->svc->moveOpportunity($opportunity, (int) $r->validate(['stage_id' => 'required|exists:pipeline_stages,id'])['stage_id']);
        return back()->with('success', 'Moved');
    }
    public function closeOpportunity(SalesOpportunity $opportunity, Request $r)
    {
        $this->svc->closeOpportunity($opportunity, $r->input('status'), $r->input('lost_reason'));
        return back()->with('success', 'Opportunity '.$r->input('status'));
    }

    // Targets
    public function targets()
    {
        $targets = SalesTarget::latest()->get()->map(fn($t) => ['t' => $t, 'a' => $this->svc->targetAchievement($t)]);
        return view('sales::targets', compact('targets'));
    }
    public function storeTarget(Request $r)
    {
        $this->svc->setTarget($r->validate(['scope' => 'required', 'scope_id' => 'nullable|integer',
            'period_type' => 'required|in:monthly,quarterly,annual', 'period' => 'required',
            'metric' => 'required|in:revenue,new_customers,shipments,contracts', 'target' => 'required|numeric']));
        return back()->with('success', 'Target saved');
    }

    // Contracts
    public function contracts() { return view('sales::contracts', ['contracts' => CommercialContract::latest()->paginate(20)]); }
    public function showContract(CommercialContract $contract) { return view('sales::contract-show', ['c' => $contract->load('versions','customer','partner')]); }
    public function storeContract(Request $r)
    {
        $this->svc->createContract($r->validate([
            'type' => 'required|in:corporate,sme,agent,broker,carrier,special_rate,partnership',
            'customer_id' => 'nullable|exists:customers,id', 'partner_id' => 'nullable|exists:commercial_partners,id',
            'start_date' => 'nullable|date', 'expiry_date' => 'nullable|date|after:start_date', 'terms' => 'nullable|array',
        ]) + ['branch_id' => $this->bid(), 'owner_id' => auth()->id()], auth()->id());
        return back()->with('success', 'Contract drafted');
    }
    public function transitionContract(CommercialContract $contract, Request $r)
    {
        if ($r->input('to') === 'active' && $r->filled('signature_evidence'))
            $contract->update(['signature_evidence' => $r->input('signature_evidence')]);
        $contract->transitionTo($r->input('to'));
        return back()->with('success', 'Contract '.$r->input('to'));
    }

    // Partners
    public function partners(Request $r)
    {
        return view('sales::partners', ['partners' => CommercialPartner::when($r->type, fn($q,$t) => $q->where('type',$t))->latest()->paginate(20)]);
    }
    public function showPartner(CommercialPartner $partner)
    {
        return view('sales::partner-show', ['p' => $partner, 'perf' => $this->svc->partnerPerformance($partner)]);
    }
    public function storePartner(Request $r)
    {
        $this->svc->registerPartner($r->validate(['type' => 'required|in:agent,broker', 'name' => 'required',
            'email' => 'nullable|email', 'phone' => 'nullable|string', 'registration_ref' => 'nullable|string'])
            + ['branch_id' => $this->bid()]);
        return back()->with('success', 'Partner registered');
    }
    public function transitionPartner(CommercialPartner $partner, Request $r)
    {
        $partner->transitionTo($r->input('to'));
        return back()->with('success', 'Partner '.$r->input('to'));
    }

    // Commissions
    public function commissions() { return view('sales::commissions', ['records' => CommissionRecord::latest()->paginate(20), 'rules' => CommissionRule::latest()->get()]); }
    public function storeCommissionRule(Request $r)
    {
        CommissionRule::create($r->validate(['beneficiary_type' => 'required|in:agent,broker,officer',
            'beneficiary_id' => 'required|integer', 'basis' => 'required|in:revenue_pct,per_shipment,per_acquisition,tiered',
            'rate' => 'nullable|numeric', 'fixed_amount' => 'nullable|numeric', 'partner_id' => 'nullable|exists:commercial_partners,id']));
        return back()->with('success', 'Rule saved');
    }
    public function transitionCommission(CommissionRecord $commission, Request $r)
    {
        if ($r->input('to') === 'paid') $this->svc->settleCommission($commission, $r->input('settlement_ref', ''));
        else $commission->transitionTo($r->input('to'), auth()->id());
        return back()->with('success', 'Commission '.$r->input('to'));
    }

    // Pricing sim + approvals
    public function simulate(Request $r)
    {
        $result = $r->filled('simulate') ? $this->svc->simulatePrice($r->all()) : null;
        return view('sales::simulate', ['result' => $result, 'campaigns' => CommercialCampaign::where('status','active')->get()]);
    }
    public function approvals() { return view('sales::approvals', ['approvals' => PricingApproval::latest()->paginate(20)]); }
    public function requestApproval(Request $r)
    {
        $this->svc->requestPricingApproval($r->validate(['entity_type' => 'required', 'requested_value' => 'required|numeric',
            'reason' => 'required', 'original_value' => 'nullable|numeric', 'entity_ref' => 'nullable|string']), auth()->id());
        return back()->with('success', 'Approval requested');
    }
    public function decideApproval(PricingApproval $approval, Request $r)
    {
        $this->svc->decidePricing($approval, $r->input('decision'), auth()->id());
        return back()->with('success', 'Decision recorded');
    }

    // Campaigns
    public function campaigns() { return view('sales::campaigns', ['campaigns' => CommercialCampaign::latest()->paginate(20)]); }
    public function storeCampaign(Request $r)
    {
        CommercialCampaign::create($r->validate(['name' => 'required', 'type' => 'required',
            'start_date' => 'required|date', 'end_date' => 'required|date|after:start_date',
            'discount_type' => 'nullable|in:percent,fixed', 'discount_value' => 'nullable|numeric',
            'usage_limit' => 'nullable|integer', 'segment_code' => 'nullable|string'])
            + ['reference' => 'XER-CAM-'.now()->format('Y').'-'.str_pad(CommercialCampaign::count()+1, 6, '0', STR_PAD_LEFT),
               'branch_id' => $this->bid()]);
        return back()->with('success', 'Campaign created');
    }
}
