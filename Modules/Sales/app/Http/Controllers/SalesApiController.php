<?php
namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Sales\Models\CommercialPartner;
use Modules\Sales\Models\SalesOpportunity;
use Modules\Sales\Services\SalesService;

class SalesApiController extends Controller
{
    public function __construct(private SalesService $svc) {}

    public function dashboard() { return response()->json($this->svc->dashboard(auth()->user()->employee?->branch_id)); }
    public function forecast()  { return response()->json(['estimate' => true, 'forecast' => $this->svc->forecast(auth()->user()->employee?->branch_id)]); }
    public function analytics() { return response()->json($this->svc->revenueAnalytics(auth()->user()->employee?->branch_id)); }

    public function opportunities(Request $r)
    {
        return response()->json(SalesOpportunity::forBranch(auth()->user()->employee?->branch_id)
            ->when($r->status, fn($q,$s) => $q->where('status',$s))->with('stage','customer','owner')->paginate(25));
    }
    public function storeOpportunity(Request $r)
    {
        $o = $this->svc->createOpportunity($r->validate(['name' => 'required', 'lead_id' => 'nullable|exists:leads,id',
            'customer_id' => 'nullable|exists:customers,id', 'expected_revenue' => 'nullable|numeric',
            'expected_close' => 'nullable|date', 'service_category' => 'nullable|string'])
            + ['branch_id' => auth()->user()->employee?->branch_id, 'owner_id' => auth()->id()]);
        return response()->json($o, 201);
    }
    public function moveOpportunity(SalesOpportunity $opportunity, Request $r)
    {
        return response()->json($this->svc->moveOpportunity($opportunity, (int) $r->validate(['stage_id' => 'required'])['stage_id']));
    }
    public function simulate(Request $r) { return response()->json($this->svc->simulatePrice($r->all())); }
    public function partnerPerformance(CommercialPartner $partner)
    {
        return response()->json(['partner' => $partner->reference, 'performance' => $this->svc->partnerPerformance($partner)]);
    }
}
