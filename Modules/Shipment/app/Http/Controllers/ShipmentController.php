<?php
namespace Modules\Shipment\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Shipment\Services\ShipmentService;
use Modules\Shipment\Models\{Shipment, Airline};

class ShipmentController extends Controller
{
    public function __construct(protected ShipmentService $svc) {}
    private function check(string $perm) { abort_unless(request()->user()->can($perm), 403); }

    public function dashboard(Request $r) { return view('shipment::dashboard', ['stats' => $this->svc->dashboard($r->user())]); }
    public function index(Request $r)
    {
        $s = Shipment::for($r->user())->with('originBranch','airline')->latest();
        if ($f = $r->input('status')) $s->where('status', $f);
        if ($q = $r->input('q')) $s->where('reference', 'like', "%{$q}%");
        return view('shipment::index', ['shipments' => $s->paginate(20)->withQueryString()]);
    }
    public function create() { $this->check('shipment.create'); return view('shipment::create', ['airlines' => Airline::where('status','active')->get()]); }
    public function store(Request $r)
    {
        $this->check('shipment.create');
        $d = $r->validate(['origin_airport' => 'nullable|string|max:10', 'destination_airport' => 'nullable|string|max:10',
            'airline_id' => 'nullable|exists:airlines,id', 'planned_departure' => 'nullable|date', 'planned_arrival' => 'nullable|date',
            'type' => 'nullable|in:consolidated,single,partial', 'destination_branch_id' => 'nullable|exists:branches,id']);
        $d['origin_branch_id'] = $r->user()->currentAssignment?->branch_id ?? \Modules\Organization\Models\Branch::value('id');
        $s = $this->svc->create($d, $r->user()->id);
        return redirect()->route('shipment.show', $s)->with('success', "Shipment {$s->reference} created.");
    }
    public function show(Request $r, Shipment $shipment)
    {
        abort_unless($shipment->origin_branch_id === $r->user()->currentAssignment?->branch_id || $r->user()->can('shipment.all_branches'), 403);
        $shipment->load(['packages','events.user','bookings.airline','waybills','documents','manifests','dispatches','handovers','exceptions','splits']);
        return view('shipment::show', ['s' => $shipment, 'readiness' => $this->svc->readiness($shipment)]);
    }
    public function transition(Request $r, Shipment $shipment)
    {
        $this->check('shipment.update');
        $d = $r->validate(['to' => 'required|string|in:' . implode(',', Shipment::STATUSES)]);
        $this->svc->transition($shipment, $d['to'], $r->user()->id);
        return back()->with('success', 'Status updated.');
    }
    public function allocate(Request $r, Shipment $shipment)
    {
        $this->check('shipment.allocate');
        $d = $r->validate(['package_ids' => 'required|array|min:1', 'package_ids.*' => 'integer']);
        $n = $this->svc->allocate($shipment, $d['package_ids'], $r->user()->id);
        return back()->with('success', "{$n} package(s) allocated.");
    }
    public function unallocate(Request $r, Shipment $shipment, int $package)
    {
        $this->check('shipment.allocate');
        $this->svc->removePackage($shipment, $package, $r->user()->id, $r->input('reason', 'manual removal'));
        return back()->with('success', 'Package removed.');
    }
    public function split(Request $r, Shipment $shipment)
    {
        $this->check('shipment.split');
        $d = $r->validate(['package_ids' => 'required|array|min:1', 'package_ids.*' => 'integer', 'reason' => 'required|string']);
        $child = $this->svc->split($shipment, $d['package_ids'], $r->user()->id, $d['reason']);
        return redirect()->route('shipment.show', $child)->with('success', "Split created {$child->reference}.");
    }
    public function book(Request $r, Shipment $shipment)
    {
        $this->check('shipment.booking.manage');
        $d = $r->validate(['airline_id' => 'required|exists:airlines,id', 'route' => 'nullable|string',
            'planned_departure' => 'nullable|date', 'cargo_category' => 'nullable|string', 'handling_requirements' => 'nullable|string']);
        $this->svc->book($shipment, $d, $r->user()->id);
        return back()->with('success', 'Booking created.');
    }
    public function confirmBooking(Request $r, int $booking)
    {
        $this->check('shipment.booking.confirm');
        $d = $r->validate(['booking_ref' => 'nullable|string', 'flight_no' => 'nullable|string', 'airline_response' => 'nullable|string']);
        $this->svc->confirmBooking(\Modules\Shipment\Models\AirlineBooking::findOrFail($booking), $d, $r->user()->id);
        return back()->with('success', 'Booking confirmed.');
    }
    public function awb(Request $r, Shipment $shipment)
    {
        $this->check('shipment.awb.create');
        $d = $r->validate(['awb_number' => 'required|string', 'kind' => 'nullable|in:hawb,mawb', 'shipper' => 'nullable|string',
            'consignee' => 'nullable|string', 'goods_description' => 'nullable|string', 'customer_id' => 'nullable|exists:customers,id']);
        $this->svc->createAwb($shipment, $d, $r->user()->id);
        return back()->with('success', 'AWB created.');
    }
    public function issueAwb(Request $r, int $awb)
    {
        $this->check('shipment.awb.issue');
        $this->svc->issueAwb(\Modules\Shipment\Models\AirWaybill::findOrFail($awb), $r->user()->id);
        return back()->with('success', 'AWB issued.');
    }
    public function manifest(Request $r, Shipment $shipment)
    {
        $this->check('shipment.manifest.manage');
        $this->svc->manifest($shipment, $r->only('booking_id','flight_id'), $r->user()->id);
        return back()->with('success', 'Manifest created.');
    }
    public function reconcile(Request $r, int $manifest)
    {
        $this->check('shipment.manifest.manage');
        $d = $r->validate(['scanned_count' => 'required|integer|min:0', 'scanned_weight' => 'nullable|numeric']);
        $this->svc->reconcile(\Modules\Shipment\Models\CargoManifest::findOrFail($manifest), $d['scanned_count'], $d['scanned_weight'] ?? null, $r->user()->id);
        return back()->with('success', 'Reconciliation recorded.');
    }
    public function approveManifest(Request $r, int $manifest)
    {
        $this->check('shipment.manifest.approve');
        $this->svc->approveManifest(\Modules\Shipment\Models\CargoManifest::findOrFail($manifest), $r->user()->id);
        return back()->with('success', 'Manifest approved.');
    }
    public function dispatch(Request $r, Shipment $shipment)
    {
        $this->check('shipment.dispatch.manage');
        $this->svc->dispatch($shipment, $r->only('vehicle','driver','notes'), $r->user()->id);
        return back()->with('success', 'Dispatch scheduled.');
    }
    public function handover(Request $r, Shipment $shipment)
    {
        $this->check('shipment.handover.confirm');
        $d = $r->validate(['accepted' => 'required|array', 'accepted.*' => 'integer', 'rejected' => 'nullable|array',
            'airline_rep' => 'nullable|string', 'receipt_evidence' => 'nullable|string']);
        $h = $this->svc->handover($shipment, $d['accepted'], $d['rejected'] ?? [], $r->user()->id, $r->only('airline_rep','receipt_evidence'));
        return back()->with('success', "Handover {$h->reference}: {$h->accepted_packages}/{$h->expected_packages} ({$h->status}).");
    }
    public function exceptions(Request $r)
    {
        return view('shipment::exceptions', ['exceptions' => \Modules\Shipment\Models\ShipmentException::with('shipment')->latest()->paginate(20)]);
    }
}
