<?php
namespace Modules\Arrival\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Arrival\Services\ArrivalService;
use Modules\Arrival\Models\{ShipmentArrival, CustomsCase, AirportCollection, DistributionBatch, ClearingAgent};
use Modules\Shipment\Models\Shipment;
use Modules\Organization\Models\Branch;

class ArrivalController extends Controller
{
    public function __construct(protected ArrivalService $svc) {}
    private function check(string $p) { abort_unless(request()->user()->can($p), 403); }

    public function dashboard(Request $r) { return view('arrival::dashboard', ['stats' => $this->svc->dashboard($r->user())]); }
    public function index(Request $r)
    {
        $q = ShipmentArrival::for($r->user())->with('shipment','branch')->latest();
        if ($s = $r->input('status')) $q->where('status', $s);
        if ($s2 = $r->input('q')) $q->where('reference', 'like', "%{$s2}%")->orWhereHas('shipment', fn ($x) => $x->where('reference', 'like', "%{$s2}%"));
        return view('arrival::index', ['arrivals' => $q->paginate(20)->withQueryString()]);
    }
    public function create()
    {
        $this->check('arrival.create');
        return view('arrival::create', [
            'shipments' => Shipment::whereIn('status', ['departed','in_transit','transshipment','arrived'])->whereDoesntHave('arrival')->latest()->get(),
            'branches' => Branch::all()]);
    }
    public function store(Request $r)
    {
        $this->check('arrival.create');
        $d = $r->validate(['shipment_id' => 'required|exists:shipments,id', 'branch_id' => 'required|exists:branches,id',
            'destination_airport' => 'nullable|string|max:10', 'scheduled_arrival' => 'nullable|date']);
        $a = $this->svc->register(Shipment::findOrFail($d['shipment_id']), $d['branch_id'], $r->user()->id, $d);
        return redirect()->route('arrival.show', $a)->with('success', "Arrival {$a->reference} registered.");
    }
    public function show(ShipmentArrival $arrival)
    {
        $arrival->load(['shipment.packages','events.user','discrepancies.package','documents','customsCase.agent','customsCase.events','charges','collections','receipts.package']);
        return view('arrival::show', ['a' => $arrival]);
    }
    public function transition(Request $r, ShipmentArrival $arrival)
    {
        $this->check('arrival.update');
        $d = $r->validate(['to' => 'required|in:' . implode(',', ShipmentArrival::STATUSES)]);
        $this->svc->transition($arrival, $d['to'], $r->user()->id);
        return back()->with('success', 'Status updated.');
    }
    public function reconcile(Request $r, ShipmentArrival $arrival)
    {
        $this->check('arrival.reconcile');
        $d = $r->validate(['package_ids' => 'required|array|min:1', 'package_ids.*' => 'integer']);
        $res = $this->svc->reconcile($arrival, $d['package_ids'], $r->user()->id);
        return back()->with('success', 'Reconciled: ' . count($res['missing']) . ' missing, ' . count($res['excess']) . ' excess.');
    }
    public function openCase(Request $r, ShipmentArrival $arrival)
    {
        $this->check('customs.create_case');
        $this->svc->openCase($arrival, $r->only('agent_id','notes'), $r->user()->id);
        return back()->with('success', 'Customs case opened.');
    }
    public function caseTransition(Request $r, CustomsCase $case)
    {
        $this->check('customs.update_case');
        $d = $r->validate(['to' => 'required|in:' . implode(',', CustomsCase::STATUSES),
            'payment_evidence' => 'nullable|string', 'release_evidence' => 'nullable|string',
            'declaration_ref' => 'nullable|string', 'assessment_ref' => 'nullable|string',
            'assessment_amount' => 'nullable|numeric', 'duty_amount' => 'nullable|numeric', 'tax_amount' => 'nullable|numeric']);
        $this->svc->transitionCase($case, $d['to'], $r->user()->id, $d);
        return back()->with('success', 'Case updated.');
    }
    public function charge(Request $r, ShipmentArrival $arrival)
    {
        $this->check('customs.update_case');
        $this->svc->recordCharge($arrival, $r->only('category','amount','currency','customer_id','source','supporting_doc'), $r->user()->id);
        return back()->with('success', 'Charge recorded.');
    }
    public function requestCollection(Request $r, ShipmentArrival $arrival)
    {
        $this->check('collection.manage');
        $this->svc->requestCollection($arrival, $r->only('vehicle','driver','agent_id','notes'), $r->user()->id);
        return back()->with('success', 'Collection requested.');
    }
    public function approveCollection(Request $r, AirportCollection $collection)
    {
        $this->check('collection.manage');
        $this->svc->approveCollection($collection, $r->user()->id, $r->input('airport_release_ref'));
        return back()->with('success', 'Collection approved.');
    }
    public function confirmCollection(Request $r, AirportCollection $collection)
    {
        $this->check('collection.confirm');
        $d = $r->validate(['package_ids' => 'required|array|min:1', 'package_ids.*' => 'integer']);
        $this->svc->confirmCollection($collection, $d['package_ids'], $r->user()->id);
        return back()->with('success', 'Collection confirmed.');
    }
    public function receive(Request $r, ShipmentArrival $arrival)
    {
        $this->check('warehouse_tz.receive');
        $d = $r->validate(['package_id' => 'required|exists:cargo_packages,id', 'condition' => 'nullable|in:good,damaged,unidentified',
            'location_id' => 'nullable|exists:warehouse_locations,id']);
        $this->svc->receivePackage($arrival, $d['package_id'], $r->user()->id, $d);
        return back()->with('success', 'Package received.');
    }
    public function agents() { return view('arrival::agents', ['agents' => ClearingAgent::latest()->paginate(20)]); }
    public function storeAgent(Request $r)
    {
        $this->check('customs.manage_agents');
        ClearingAgent::create($r->validate(['name' => 'required', 'license_ref' => 'nullable|string',
            'contact_name' => 'nullable|string', 'contact_phone' => 'nullable|string', 'contact_email' => 'nullable|email']));
        return back()->with('success', 'Agent added.');
    }
    public function batches()
    {
        return view('arrival::batches', ['batches' => DistributionBatch::with('customer','items')->latest()->paginate(20)]);
    }
    public function storeBatch(Request $r)
    {
        $this->check('distribution.manage');
        $d = $r->validate(['package_ids' => 'required|array|min:1', 'package_ids.*' => 'integer',
            'branch_id' => 'required|exists:branches,id', 'customer_id' => 'nullable|exists:customers,id', 'zone' => 'nullable|string']);
        $b = $this->svc->createBatch($d['package_ids'], $d, $r->user()->id);
        return back()->with('success', "Batch {$b->reference} created.");
    }
}
