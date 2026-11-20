<?php
namespace Modules\Delivery\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Modules\Delivery\Models\{DeliveryOrder, Driver, DeliveryAssignment};
use Modules\Delivery\Services\DeliveryService as Svc;

class DeliveryApiController extends Controller
{
    public function __construct(protected Svc $svc) {}
    public function index(Request $r)
    {
        $q = DeliveryOrder::for($r->user())->with('customer','driver')->latest();
        if ($s = $r->input('status')) $q->where('status', $s);
        return ApiResponse::paginated($q->paginate($r->integer('per_page', 20)));
    }
    public function show(DeliveryOrder $order)
    {
        $order->load(['packages','events','assignments','pod','cod']);
        return ApiResponse::success($order);
    }
    public function transition(Request $r, DeliveryOrder $order)
    {
        abort_unless($r->user()->can('delivery.update'), 403);
        $d = $r->validate(['to' => 'required|in:' . implode(',', DeliveryOrder::STATUSES)]);
        return ApiResponse::success($this->svc->transition($order, $d['to'], $r->user()->id));
    }
    // ── driver endpoints ──
    public function driverAvailability(Request $r, Driver $driver)
    {
        $d = $r->validate(['status' => 'required|in:offline,available,busy,on_delivery,on_break',
            'lat' => 'nullable|numeric|between:-90,90', 'lng' => 'nullable|numeric|between:-180,180']);
        return ApiResponse::success($this->svc->setAvailability($driver, $d['status'], $d['lat'] ?? null, $d['lng'] ?? null));
    }
    public function driverLocation(Request $r, Driver $driver)
    {
        $d = $r->validate(['lat' => 'required|numeric|between:-90,90', 'lng' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric', 'order_id' => 'nullable|exists:delivery_orders,id']);
        return ApiResponse::success($this->svc->recordLocation($driver, $d['lat'], $d['lng'], $d['accuracy'] ?? null, $d['order_id'] ?? null));
    }
    public function driverRespond(Request $r, DeliveryAssignment $assignment)
    {
        $d = $r->validate(['accept' => 'required|boolean']);
        return ApiResponse::success($this->svc->driverRespond($assignment, $d['accept']));
    }
    public function confirmDelivery(Request $r, DeliveryOrder $order)
    {
        $d = $r->validate(['recipient_name' => 'required|string', 'recipient_relation' => 'nullable|string',
            'signature_path' => 'nullable|string', 'photo_path' => 'nullable|string', 'otp_code' => 'nullable|string',
            'lat' => 'nullable|numeric', 'lng' => 'nullable|numeric', 'notes' => 'nullable|string',
            'scanned_package_ids' => 'nullable|array']);
        return ApiResponse::success($this->svc->confirmDelivery($order, $d, $order->driver_id ?? $r->user()->id));
    }
    /** Customer-facing tracking — verified events + live location only when fresh. */
    public function track(DeliveryOrder $order)
    {
        return ApiResponse::success($this->svc->track($order));
    }
}
