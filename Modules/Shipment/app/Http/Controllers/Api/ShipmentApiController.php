<?php
namespace Modules\Shipment\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Modules\Shipment\Models\Shipment;
use Modules\Shipment\Services\ShipmentService;

class ShipmentApiController extends Controller
{
    public function __construct(protected ShipmentService $svc) {}

    public function index(Request $r)
    {
        $q = Shipment::for($r->user())->with('originBranch','airline')->latest();
        if ($s = $r->input('status')) $q->where('status', $s);
        if ($q2 = $r->input('q')) $q->where('reference', 'like', "%{$q2}%");
        return ApiResponse::paginated($q->paginate($r->integer('per_page', 20)));
    }
    public function store(Request $r)
    {
        abort_unless($r->user()->can('shipment.create'), 403);
        $d = $r->validate(['origin_airport' => 'nullable|string|max:10', 'destination_airport' => 'required|string|max:10',
            'airline_id' => 'nullable|exists:airlines,id', 'planned_departure' => 'nullable|date',
            'type' => 'nullable|in:consolidated,single,partial']);
        $d['origin_branch_id'] = $r->user()->currentAssignment?->branch_id ?? \Modules\Organization\Models\Branch::value('id');
        return ApiResponse::created($this->svc->create($d, $r->user()->id));
    }
    public function show(Request $r, Shipment $shipment)
    {
        abort_unless($shipment->origin_branch_id === $r->user()->currentAssignment?->branch_id || $r->user()->can('shipment.all_branches'), 403);
        $shipment->load(['packages','events.user','bookings','waybills','manifests','handovers','exceptions']);
        return ApiResponse::success($shipment);
    }
    public function transition(Request $r, Shipment $shipment)
    {
        abort_unless($r->user()->can('shipment.update'), 403);
        $d = $r->validate(['to' => 'required|in:' . implode(',', Shipment::STATUSES)]);
        return ApiResponse::success($this->svc->transition($shipment, $d['to'], $r->user()->id));
    }
    public function allocate(Request $r, Shipment $shipment)
    {
        abort_unless($r->user()->can('shipment.allocate'), 403);
        $d = $r->validate(['package_ids' => 'required|array|min:1', 'package_ids.*' => 'integer']);
        return ApiResponse::success(['allocated' => $this->svc->allocate($shipment, $d['package_ids'], $r->user()->id)]);
    }
    public function readiness(Shipment $shipment) { return ApiResponse::success($this->svc->readiness($shipment)); }
    public function handover(Request $r, Shipment $shipment)
    {
        abort_unless($r->user()->can('shipment.handover.confirm'), 403);
        $d = $r->validate(['accepted' => 'required|array', 'accepted.*' => 'integer', 'rejected' => 'nullable|array']);
        return ApiResponse::success($this->svc->handover($shipment, $d['accepted'], $d['rejected'] ?? [], $r->user()->id));
    }
    /** Customer-facing tracking: verified events only, no internal data. */
    public function track(Request $r, Shipment $shipment)
    {
        $public = ['created','status:booked','status:dispatched_to_airport','status:handed_over','status:departed','status:in_transit','status:delayed','status:arrived','manifest_confirmed'];
        return ApiResponse::success([
            'reference' => $shipment->reference, 'status' => $shipment->status,
            'route' => $shipment->origin_airport . ' → ' . $shipment->destination_airport,
            'events' => $shipment->events()->whereIn('event', $public)->get(['event','created_at']),
        ]);
    }
}
