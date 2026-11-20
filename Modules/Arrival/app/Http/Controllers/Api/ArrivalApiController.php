<?php
namespace Modules\Arrival\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Modules\Arrival\Services\ArrivalService;
use Modules\Arrival\Models\{ShipmentArrival, DistributionBatch};

class ArrivalApiController extends Controller
{
    public function __construct(protected ArrivalService $svc) {}
    public function index(Request $r)
    {
        $q = ShipmentArrival::for($r->user())->with('shipment')->latest();
        if ($s = $r->input('status')) $q->where('status', $s);
        return ApiResponse::paginated($q->paginate($r->integer('per_page', 20)));
    }
    public function show(Request $r, ShipmentArrival $arrival)
    {
        $arrival->load(['shipment','events','discrepancies','customsCase','charges','collections']);
        return ApiResponse::success($arrival);
    }
    public function transition(Request $r, ShipmentArrival $arrival)
    {
        abort_unless($r->user()->can('arrival.update'), 403);
        $d = $r->validate(['to' => 'required|in:' . implode(',', ShipmentArrival::STATUSES), 'source' => 'nullable|in:airline_api,manual,airport,tracking_provider']);
        return ApiResponse::success($this->svc->transition($arrival, $d['to'], $r->user()->id, $d['source'] ?? 'manual'));
    }
    public function reconcile(Request $r, ShipmentArrival $arrival)
    {
        abort_unless($r->user()->can('arrival.reconcile'), 403);
        $d = $r->validate(['package_ids' => 'required|array|min:1']);
        return ApiResponse::success($this->svc->reconcile($arrival, $d['package_ids'], $r->user()->id));
    }
    /** Phase 6 handover — only eligible packages. */
    public function handover(DistributionBatch $batch)
    {
        $batch->update(['status' => 'handed_over', 'handed_over_at' => now()]);
        return ApiResponse::success($this->svc->handoverPayload($batch));
    }
}
