<?php
namespace Modules\Portal\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Portal\Services\PortalService;
use Modules\Portal\Models\{Complaint, CargoClaim, CustomerFeedback, CustomerMessage, PrivacyRequest};
use Modules\Ticketing\Models\Ticket;

/** Customer-facing portal — all data scoped to the linked customer. */
class PortalController extends Controller
{
    public function __construct(protected PortalService $svc) {}

    private function customer(Request $r)
    {
        $c = $this->svc->customerFor($r->user());
        abort_unless($c, 403, 'No customer account linked.');
        return $c;
    }

    public function dashboard(Request $r)
    {
        $c = $this->customer($r);
        return view('portal::dashboard', ['stats' => $this->svc->customerDashboard($c), 'c' => $c]);
    }
    public function tracking(Request $r)
    {
        $c = $this->customer($r);
        return view('portal::tracking', ['timeline' => $this->svc->trackingTimeline($c), 'c' => $c]);
    }
    public function tickets(Request $r)
    {
        $c = $this->customer($r);
        return view('portal::tickets', ['tickets' => Ticket::where('customer_id', $c->id)->latest()->paginate(15)]);
    }
    public function storeTicket(Request $r)
    {
        $c = $this->customer($r);
        $this->svc->createTicket($c, $r->validate(['subject' => 'required|string|max:200', 'description' => 'required|string', 'priority' => 'nullable|in:low,normal,high,urgent']));
        return back()->with('success', 'Ticket submitted.');
    }
    public function ticketShow(Request $r, Ticket $ticket)
    {
        $c = $this->customer($r);
        abort_unless($ticket->customer_id === $c->id, 403); // IDOR guard
        $ticket->load(['comments' => fn ($q) => $q->where('is_internal', false)->oldest()]);
        return view('portal::ticket-show', ['t' => $ticket]);
    }
    public function replyTicket(Request $r, Ticket $ticket)
    {
        $c = $this->customer($r);
        abort_unless($ticket->customer_id === $c->id, 403);
        $this->svc->ticketReply($ticket, $r->validate(['body' => 'required|string'])['body'], null, false);
        return back()->with('success', 'Reply sent.');
    }
    public function complaints(Request $r)
    {
        $c = $this->customer($r);
        return view('portal::complaints', ['complaints' => Complaint::where('customer_id', $c->id)->latest()->paginate(15)]);
    }
    public function storeComplaint(Request $r)
    {
        $c = $this->customer($r);
        $this->svc->submitComplaint($c, $r->validate(['category' => 'required|string', 'description' => 'required|string',
            'shipment_id' => 'nullable|integer', 'order_id' => 'nullable|integer']));
        return back()->with('success', 'Complaint submitted.');
    }
    public function claims(Request $r)
    {
        $c = $this->customer($r);
        return view('portal::claims', ['claims' => CargoClaim::where('customer_id', $c->id)->latest()->paginate(15)]);
    }
    public function storeClaim(Request $r)
    {
        $c = $this->customer($r);
        $this->svc->submitClaim($c, $r->only('type','description','claimed_amount','shipment_id','package_id','order_id','awb','incident_date','declared_value'));
        return back()->with('success', 'Claim submitted.');
    }
    public function statement(Request $r)
    {
        $c = $this->customer($r);
        return view('portal::statement', ['st' => $this->svc->statement($c, $r->input('from'), $r->input('to'))]);
    }
    public function feedback(Request $r)
    {
        $c = $this->customer($r);
        $d = $r->validate(['survey_key' => 'required|string', 'rating' => 'required|integer|min:1|max:5', 'comment' => 'nullable|string']);
        $this->svc->submitFeedback($c, $d['survey_key'], $d['rating'], $d);
        return back()->with('success', 'Thank you for your feedback.');
    }
    public function privacy(Request $r)
    {
        $c = $this->customer($r);
        $d = $r->validate(['type' => 'required|in:access,correction,export,closure,preferences', 'details' => 'nullable|string']);
        $p = $this->svc->submitPrivacyRequest($c, $d['type'], $d['details'] ?? null);
        return back()->with('success', "Request {$p->reference} submitted.");
    }
    public function messages(Request $r)
    {
        $c = $this->customer($r);
        return view('portal::messages', ['messages' => CustomerMessage::where('customer_id', $c->id)->latest()->paginate(20)]);
    }
}
