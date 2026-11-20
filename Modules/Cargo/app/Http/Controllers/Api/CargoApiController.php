<?php
namespace Modules\Cargo\Http\Controllers\Api;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Cargo\Models\{PreAlert, Receiving, CargoPackage, WarehouseLocation, CargoIncident};
use Modules\Cargo\Services\CargoService;

class CargoApiController extends Controller
{
    public function dashboard(Request $r, CargoService $svc) { return ApiResponse::success($svc->dashboard($r->user())); }

    public function preAlerts(Request $r) { return ApiResponse::paginated(PreAlert::for($r->user())->latest()->paginate(25)); }
    public function preAlertStore(Request $r, CargoService $svc)
    {
        $data = $r->validate(['branch_id' => 'required|exists:branches:id,id', 'customer_id' => 'nullable|exists:customers,id', 'expected_packages' => 'nullable|integer|min:0']);
        return ApiResponse::created($svc->createPreAlert($data, $r->user()->id));
    }

    public function receivings(Request $r) { return ApiResponse::paginated(Receiving::for($r->user())->with('customer:id,name')->latest()->paginate(25)); }
    public function receivingStart(Request $r, CargoService $svc)
    {
        $data = $r->validate(['branch_id' => 'required|exists:branches,id', 'pre_alert_id' => 'nullable|exists:cargo_pre_alerts,id',
            'customer_id' => 'nullable|exists:customers,id', 'expected_packages' => 'nullable|integer|min:0', 'unidentified' => 'boolean']);
        return ApiResponse::created($svc->startReceiving($data, $r->user()->id));
    }
    public function receivingConfirm(Receiving $receiving, Request $r, CargoService $svc)
    {
        return ApiResponse::success($svc->confirmReceiving($receiving, $r->user()->id));
    }

    public function packages(Request $r) { return ApiResponse::paginated(CargoPackage::for($r->user())->with('customer:id,name','location')->latest()->paginate(25)); }
    public function packageShow(Request $r, CargoPackage $package)
    {
        return ApiResponse::success($package->load(['customer','location.warehouse','measurements','inspections','events.user:id,name','movements']));
    }
    public function packageScan(Request $r, CargoPackage $package, CargoService $svc)
    {
        $e = $svc->scan($package, $r->validate(['event' => 'required|string|max:40'])['event'], $r->user()->id, $r->input('location'));
        return ApiResponse::success(['event' => $e, 'duplicate' => $e->meta['duplicate'] ?? false]);
    }
    public function packageMeasure(Request $r, CargoPackage $package, CargoService $svc)
    {
        $data = $r->validate(['gross_weight' => 'nullable|numeric|min:0', 'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0', 'height' => 'nullable|numeric|min:0', 'divisor' => 'nullable|integer|min:1']);
        return ApiResponse::created($svc->measure($package, $data, $r->user()->id, $r->input('kind', 'initial')));
    }
    public function packageInspect(Request $r, CargoPackage $package, CargoService $svc)
    {
        $data = $r->validate(['outcome' => 'required|in:' . implode(',', \Modules\Cargo\Models\Inspection::OUTCOMES), 'checklist' => 'nullable|array', 'comments' => 'nullable|string']);
        return ApiResponse::created($svc->inspect($package, $data, $r->user()->id));
    }
    public function packageMove(Request $r, CargoPackage $package, CargoService $svc)
    {
        $data = $r->validate(['location_id' => 'required|exists:warehouse_locations,id', 'reason' => 'nullable|string|max:40']);
        return ApiResponse::created($svc->move($package, WarehouseLocation::findOrFail($data['location_id']), $r->user()->id, $data['reason'] ?? 'transfer'));
    }
    public function incidents(Request $r) { return ApiResponse::paginated(CargoIncident::for($r->user())->latest()->paginate(25)); }
}
