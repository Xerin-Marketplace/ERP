<?php
namespace Modules\Portal\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Modules\Portal\Services\PortalService;

class PortalApiController extends Controller
{
    public function __construct(protected PortalService $svc) {}
    private function customer(Request $r)
    {
        $c = $this->svc->customerFor($r->user());
        abort_unless($c, 403);
        return $c;
    }
    public function dashboard(Request $r) { return ApiResponse::success($this->svc->customerDashboard($this->customer($r))); }
    public function tracking(Request $r) { return ApiResponse::success($this->svc->trackingTimeline($this->customer($r))); }

    /** POST /api/v1/track — guest tracking by XRN + email/phone. */
    public function guestTrack(Request $r)
    {
        $r->validate(['tracking_no' => 'required|string|max:20', 'contact' => 'required|string|max:120']);
        $result = $this->svc->guestTrack($r->input('tracking_no'), $r->input('contact'));
        abort_unless($result, 404, 'No shipment found for those details.');
        return ApiResponse::success($result);
    }
    public function statement(Request $r) { return ApiResponse::success($this->svc->statement($this->customer($r), $r->input('from'), $r->input('to'))); }
    public function storeTicket(Request $r)
    {
        return ApiResponse::created($this->svc->createTicket($this->customer($r),
            $r->validate(['subject' => 'required|string|max:200', 'description' => 'required|string'])));
    }
    public function storeClaim(Request $r)
    {
        return ApiResponse::created($this->svc->submitClaim($this->customer($r), $r->all()));
    }
}
