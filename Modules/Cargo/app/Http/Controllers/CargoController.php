<?php
namespace Modules\Cargo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Cargo\Models\{PreAlert, Receiving, CargoPackage, Warehouse, WarehouseLocation, CargoIncident};
use Modules\Cargo\Services\CargoService;

class CargoController extends Controller
{
    public function dashboard(CargoService $svc)
    {
        $u = auth()->user();
        return view('cargo::dashboard', [
            'stats' => $svc->dashboard($u),
            'recent' => Receiving::for($u)->with('customer:id,name','branch:id,name')->latest()->limit(8)->get(),
            'alerts' => PreAlert::for($u)->with('customer:id,name')->whereNotIn('status', ['cancelled','fully_received'])->latest()->limit(8)->get(),
        ]);
    }

    // ── Pre-alerts ────────────────────────────────────
    public function preAlerts(Request $r)
    {
        return view('cargo::pre-alerts', ['alerts' => PreAlert::for($r->user())->with('customer:id,name','branch:id,name')
            ->when($r->status, fn($q,$v)=>$q->where('status',$v))->latest()->paginate(25)->withQueryString()]);
    }
    public function preAlertStore(Request $r, CargoService $svc)
    {
        $data = $r->validate(['customer_id' => 'nullable|exists:customers,id', 'handover_id' => 'nullable|exists:commercial_handovers,id',
            'supplier_name' => 'nullable|string|max:255', 'supplier_ref' => 'nullable|string|max:100', 'branch_id' => 'required|exists:branches,id',
            'expected_date' => 'nullable|date', 'expected_packages' => 'nullable|integer|min:0', 'cargo_description' => 'nullable|string',
            'expected_weight' => 'nullable|numeric|min:0', 'delivery_instructions' => 'nullable|string']);
        $svc->createPreAlert($data + ['status' => 'awaiting_delivery'], $r->user()->id);
        return back()->with('ok', 'Pre-alert created.');
    }

    // ── Receiving ─────────────────────────────────────
    public function receivings(Request $r)
    {
        return view('cargo::receivings', ['receivings' => Receiving::for($r->user())->with('customer:id,name','branch:id,name','preAlert:id,reference')
            ->when($r->status, fn($q,$v)=>$q->where('status',$v))->latest()->paginate(25)->withQueryString(),
            'alerts' => PreAlert::for($r->user())->whereNotIn('status',['cancelled','fully_received'])->get()]);
    }
    public function receivingStart(Request $r, CargoService $svc)
    {
        $data = $r->validate(['pre_alert_id' => 'nullable|exists:cargo_pre_alerts,id', 'customer_id' => 'nullable|exists:customers,id',
            'handover_id' => 'nullable|exists:commercial_handovers,id', 'supplier_name' => 'nullable|string|max:255',
            'branch_id' => 'required|exists:branches,id', 'expected_packages' => 'nullable|integer|min:0',
            'delivery_note_ref' => 'nullable|string|max:100', 'unidentified' => 'boolean', 'notes' => 'nullable|string']);
        if ($a = ($data['pre_alert_id'] ?? null) ? PreAlert::find($data['pre_alert_id']) : null) {
            $data += ['customer_id' => $data['customer_id'] ?? $a->customer_id, 'handover_id' => $data['handover_id'] ?? $a->handover_id,
                'supplier_name' => $data['supplier_name'] ?? $a->supplier_name, 'expected_packages' => $data['expected_packages'] ?? $a->expected_packages];
        }
        $rec = $svc->startReceiving($data, $r->user()->id);
        return redirect()->route('cargo.receivings.show', $rec)->with('ok', "Receiving {$rec->reference} started.");
    }
    public function receivingShow(Receiving $receiving)
    {
        $receiving->load(['packages.location','customer:id,code,name','preAlert','branch:id,name','receiver:id,name']);
        return view('cargo::receiving-show', ['receiving' => $receiving]);
    }
    public function receivingPackages(Request $r, Receiving $receiving, CargoService $svc)
    {
        abort_if($receiving->status !== 'in_progress', 422, 'Receiving already confirmed.');
        $data = $r->validate(['count' => 'required|integer|min:1|max:500', 'package_type' => 'nullable|string|max:40',
            'goods_description' => 'nullable|string', 'cargo_category' => 'nullable|string|max:50', 'handling' => 'nullable|string|max:40']);
        $svc->registerPackages($receiving, $data['count'], array_diff_key($data, ['count'=>1]), $r->user()->id);
        return back()->with('ok', "{$data['count']} package(s) registered.");
    }
    public function receivingConfirm(Receiving $receiving, CargoService $svc, Request $r)
    {
        $rec = $svc->confirmReceiving($receiving, $r->user()->id);
        return back()->with('ok', "Receiving confirmed: {$rec->outcome}.");
    }

    // ── Packages ──────────────────────────────────────
    public function packages(Request $r)
    {
        return view('cargo::packages', ['packages' => CargoPackage::for($r->user())->with('customer:id,name','location','branch:id,name')
            ->when($r->q, fn($q,$v)=>$q->where(fn($w)=>$w->where('reference','like',"%{$v}%")->orWhere('goods_description','like',"%{$v}%")))
            ->when($r->status, fn($q,$v)=>$q->where('status',$v))->latest()->paginate(25)->withQueryString()]);
    }
    public function packageShow(CargoPackage $package)
    {
        $package->load(['customer:id,code,name','branch:id,name','location.warehouse','measurements.measurer:id,name','inspections.inspector:id,name','repackagings','events.user:id,name','movements.from','movements.to']);
        return view('cargo::package-show', ['package' => $package,
            'locations' => WarehouseLocation::where('status','active')->with('warehouse')->orderBy('code')->get()]);
    }
    public function packageLabel(CargoPackage $package)
    {
        return view('cargo::label', ['package' => $package->load('customer:id,code,name','branch:id,code')]);
    }
    public function packageMeasure(Request $r, CargoPackage $package, CargoService $svc)
    {
        $data = $r->validate(['gross_weight' => 'nullable|numeric|min:0', 'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0', 'height' => 'nullable|numeric|min:0',
            'divisor' => 'nullable|integer|min:1', 'reason' => 'nullable|string|max:255', 'kind' => 'required|in:initial,reweigh']);
        $svc->measure($package, $data, $r->user()->id, $data['kind']);
        return back()->with('ok', 'Measurement recorded.');
    }
    public function packageInspect(Request $r, CargoPackage $package, CargoService $svc)
    {
        $data = $r->validate(['checklist' => 'nullable|array', 'outcome' => 'required|in:' . implode(',', \Modules\Cargo\Models\Inspection::OUTCOMES),
            'comments' => 'nullable|string', 'required_actions' => 'nullable|string']);
        $svc->inspect($package, $data, $r->user()->id);
        return back()->with('ok', "Inspection: {$data['outcome']}.");
    }
    public function packageRelease(CargoPackage $package, CargoService $svc, Request $r)
    {
        $svc->releaseHold($package, $r->user()->id);
        return back()->with('ok', 'Hold released.');
    }
    public function packageIdentify(Request $r, CargoPackage $package, CargoService $svc)
    {
        $data = $r->validate(['customer_id' => 'required|exists:customers,id']);
        $svc->identifyPackage($package, $data['customer_id'], $r->user()->id);
        return back()->with('ok', 'Package linked to customer.');
    }
    public function packageMove(Request $r, CargoPackage $package, CargoService $svc)
    {
        $data = $r->validate(['location_id' => 'required|exists:warehouse_locations,id', 'reason' => 'required|string|max:40']);
        $pkgUpdate = $data['reason'] === 'inspection_to_storage' ? ['status' => 'stored'] : [];
        $svc->move($package, WarehouseLocation::findOrFail($data['location_id']), $r->user()->id, $data['reason'], $pkgUpdate);
        return back()->with('ok', 'Package moved.');
    }
    public function packageScan(Request $r, CargoPackage $package, CargoService $svc)
    {
        $e = $svc->scan($package, $r->validate(['event' => 'required|string|max:40'])['event'], $r->user()->id, $r->input('location'));
        return back()->with('ok', $e->meta['duplicate'] ?? false ? 'Duplicate scan ignored.' : 'Scan recorded.');
    }

    // ── Warehouse ─────────────────────────────────────
    public function warehouses()
    {
        return view('cargo::warehouses', ['warehouses' => Warehouse::with('branch:id,name','locations')->get()]);
    }
    public function locationStore(Request $r)
    {
        $data = $r->validate(['warehouse_id' => 'required|exists:warehouses,id', 'code' => 'required|string|max:40',
            'zone' => 'nullable|string|max:10', 'type' => 'required|string|max:20', 'capacity' => 'nullable|integer|min:0']);
        WarehouseLocation::firstOrCreate(['warehouse_id' => $data['warehouse_id'], 'code' => $data['code']], $data);
        return back()->with('ok', 'Location saved.');
    }
    public function incidents(Request $r)
    {
        return view('cargo::incidents', ['incidents' => CargoIncident::for($r->user())->with('package:id,reference','branch:id,name')
            ->when($r->status, fn($q,$v)=>$q->where('status',$v))->latest()->paginate(25)->withQueryString()]);
    }
    public function incidentResolve(Request $r, CargoIncident $incident)
    {
        $incident->update(['status' => 'resolved', 'resolution' => $r->validate(['resolution' => 'required|string|max:1000'])['resolution'],
            'resolved_by' => $r->user()->id, 'resolved_at' => now()]);
        return back()->with('ok', 'Incident resolved.');
    }
}
