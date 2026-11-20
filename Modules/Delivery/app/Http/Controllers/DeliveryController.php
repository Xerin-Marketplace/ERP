<?php
namespace Modules\Delivery\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Delivery\Services\DeliveryService as Svc;
use Modules\Delivery\Models\{DeliveryOrder, Driver, Vehicle, DeliveryService as Service, DeliveryZone, DeliveryAssignment, DeliveryReturn, CodCollection};

class DeliveryController extends Controller
{
    public function __construct(protected Svc $svc) {}
    private function check(string $p) { abort_unless(request()->user()->can($p), 403); }

    public function dashboard(Request $r) { return view('delivery::dashboard', ['stats' => $this->svc->dashboard($r->user())]); }
    public function index(Request $r)
    {
        $q = DeliveryOrder::for($r->user())->with('customer','driver','zone','service')->latest();
        if ($s = $r->input('status')) $q->where('status', $s);
        if ($q2 = $r->input('q')) $q->where('reference', 'like', "%{$q2}%");
        return view('delivery::index', ['orders' => $q->paginate(20)->withQueryString()]);
    }
    public function create()
    {
        $this->check('delivery.create');
        return view('delivery::create', [
            'services' => Service::where('active', true)->get(), 'zones' => DeliveryZone::where('active', true)->get(),
            'batches' => \Modules\Arrival\Models\DistributionBatch::where('status', 'open')->get(),
            'customers' => \Modules\Commercial\Models\Customer::limit(100)->get()]);
    }
    public function store(Request $r)
    {
        $this->check('delivery.create');
        $d = $r->validate(['customer_id' => 'required|exists:customers,id', 'branch_id' => 'required|exists:branches,id',
            'package_ids' => 'required|array|min:1', 'package_ids.*' => 'integer',
            'service_id' => 'nullable|exists:delivery_services,id', 'zone_id' => 'nullable|exists:delivery_zones,id',
            'batch_id' => 'nullable|exists:distribution_batches,id', 'scheduled_date' => 'nullable|date',
            'cod_amount' => 'nullable|numeric', 'delivery_type' => 'nullable|in:delivery,collection', 'instructions' => 'nullable|string']);
        $pids = $d['package_ids']; unset($d['package_ids']);
        $o = $this->svc->create($d, $pids, $r->user()->id);
        return redirect()->route('delivery.show', $o)->with('success', "Order {$o->reference} created.");
    }
    public function show(DeliveryOrder $order)
    {
        $order->load(['packages','events','assignments.driver','attempts','pod','returns','cod','collection','driver','vehicle','service','zone','customer']);
        return view('delivery::show', ['o' => $order,
            'drivers' => Driver::where('branch_id', $order->branch_id)->where('verification_status','verified')->get(),
            'vehicles' => Vehicle::where('branch_id', $order->branch_id)->where('status','active')->get()]);
    }
    public function transition(Request $r, DeliveryOrder $order)
    {
        $this->check('delivery.update');
        $d = $r->validate(['to' => 'required|in:' . implode(',', DeliveryOrder::STATUSES)]);
        $this->svc->transition($order, $d['to'], $r->user()->id);
        return back()->with('success', 'Status updated.');
    }
    public function assign(Request $r, DeliveryOrder $order)
    {
        $this->check('delivery.dispatch');
        $d = $r->validate(['driver_id' => 'required|exists:drivers,id', 'vehicle_id' => 'nullable|exists:vehicles,id']);
        $this->svc->assign($order, Driver::findOrFail($d['driver_id']), isset($d['vehicle_id']) ? Vehicle::find($d['vehicle_id']) : null, $r->user()->id);
        return back()->with('success', 'Assigned.');
    }
    public function autoAssign(Request $r, DeliveryOrder $order)
    {
        $this->check('delivery.dispatch');
        $a = $this->svc->autoAssign($order, $r->user()->id);
        return back()->with($a ? 'success' : 'error', $a ? 'Auto-assigned.' : 'No eligible driver — order stays in unassigned queue.');
    }
    public function fail(Request $r, DeliveryOrder $order)
    {
        $this->check('delivery.update');
        $d = $r->validate(['reason' => 'required|in:' . implode(',', \Modules\Delivery\Models\DeliveryAttempt::REASONS), 'notes' => 'nullable|string']);
        $this->svc->recordFailure($order, $d['reason'], $order->driver_id ?? 0, $d);
        return back()->with('success', 'Failure recorded.');
    }
    public function reschedule(Request $r, DeliveryOrder $order)
    {
        $this->check('delivery.update');
        $d = $r->validate(['date' => 'required|date']);
        $this->svc->reschedule($order, $d['date'], $r->user()->id);
        return back()->with('success', 'Rescheduled.');
    }
    public function initiateReturn(Request $r, DeliveryOrder $order)
    {
        $this->check('delivery.returns');
        $this->svc->initiateReturn($order, $r->input('reason', 'customer_request'), $r->user()->id);
        return back()->with('success', 'Return initiated.');
    }
    public function receiveReturn(Request $r, DeliveryReturn $return)
    {
        $this->check('delivery.returns');
        $this->svc->receiveReturn($return, $r->user()->id);
        return back()->with('success', 'Return received.');
    }
    public function cod(Request $r, DeliveryOrder $order)
    {
        $this->check('delivery.cod');
        $this->svc->recordCod($order, $r->only('amount','method','payment_ref'), $order->driver_id ?? 0);
        return back()->with('success', 'COD recorded.');
    }
    public function reconcileCod(Request $r, CodCollection $cod)
    {
        $this->check('delivery.cod');
        $this->svc->reconcileCod($cod, $r->user()->id, $r->input('payment_ref'));
        return back()->with('success', 'COD reconciled.');
    }
    public function drivers() { return view('delivery::drivers', ['drivers' => Driver::with('latestLocation')->latest()->paginate(20)]); }
    public function storeDriver(Request $r)
    {
        $this->check('delivery.drivers');
        $d = $r->validate(['name' => 'required', 'phone' => 'nullable|string', 'branch_id' => 'required|exists:branches,id',
            'license_no' => 'nullable|string', 'license_expiry' => 'nullable|date', 'category' => 'nullable|string']);
        $drv = new Driver($d);
        $drv->forceFill(['reference' => 'XER-DRV-' . str_pad((string) (Driver::count() + 1), 6, '0', STR_PAD_LEFT)])->save();
        return back()->with('success', 'Driver added.');
    }
    public function verifyDriver(Request $r, Driver $driver)
    {
        $this->check('delivery.drivers');
        $driver->update(['verification_status' => $r->input('status', 'verified')]);
        return back()->with('success', 'Driver updated.');
    }
    public function vehicles() { return view('delivery::vehicles', ['vehicles' => Vehicle::latest()->paginate(20)]); }
    public function storeVehicle(Request $r)
    {
        $this->check('delivery.vehicles');
        Vehicle::create($r->validate(['registration_no' => 'required|unique:vehicles,registration_no',
            'branch_id' => 'required|exists:branches,id', 'type' => 'nullable|string', 'capacity_kg' => 'nullable|numeric',
            'capacity_packages' => 'nullable|integer', 'insurance_expiry' => 'nullable|date', 'inspection_expiry' => 'nullable|date']));
        return back()->with('success', 'Vehicle added.');
    }
    public function services() { return view('delivery::services', ['services' => Service::all(), 'zones' => DeliveryZone::all()]); }
    public function storeService(Request $r)
    {
        $this->check('delivery.config');
        Service::create($r->validate(['code' => 'required|unique:delivery_services,code', 'name' => 'required',
            'pricing_method' => 'required|in:zone_fixed,weight,distance,hybrid,contract',
            'base_fee' => 'nullable|numeric', 'per_kg_fee' => 'nullable|numeric', 'per_km_fee' => 'nullable|numeric',
            'min_fee' => 'nullable|numeric', 'max_fee' => 'nullable|numeric', 'max_package_weight' => 'nullable|numeric',
            'requires_signature' => 'nullable|boolean', 'requires_otp' => 'nullable|boolean', 'requires_photo' => 'nullable|boolean']));
        return back()->with('success', 'Service added.');
    }
    public function storeZone(Request $r)
    {
        $this->check('delivery.config');
        DeliveryZone::create($r->validate(['name' => 'required', 'region' => 'nullable|string', 'district' => 'nullable|string',
            'city' => 'nullable|string', 'fee' => 'nullable|numeric', 'eta' => 'nullable|string', 'branch_id' => 'nullable|exists:branches,id', 'remote' => 'nullable|boolean']));
        return back()->with('success', 'Zone added.');
    }
}
