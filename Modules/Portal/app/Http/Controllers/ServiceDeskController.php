<?php
namespace Modules\Portal\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Portal\Services\PortalService;
use Modules\Portal\Models\{Complaint, CargoClaim, PrivacyRequest, CustomerFeedback};
use Modules\Commercial\Models\Customer;
use Modules\Ticketing\Models\Ticket;

/** Internal customer-service workspace. */
class ServiceDeskController extends Controller
{
    public function __construct(protected PortalService $svc) {}
    private function check(string $p) { abort_unless(request()->user()->can($p), 403); }

    public function dashboard()
    {
        $this->check('portal.staff');
        return view('portal::desk-dashboard', ['stats' => [
            'open_tickets' => Ticket::where('source','customer_portal')->whereNotIn('status',['resolved','closed'])->count(),
            'open_complaints' => Complaint::whereNotIn('status',['resolved','closed','rejected'])->count(),
            'claims_investigating' => CargoClaim::whereIn('status',['under_review','investigation','additional_evidence'])->count(),
            'claims_pending_approval' => CargoClaim::where('status','pending_approval')->count(),
            'claims_settlement' => CargoClaim::where('status','settlement_pending')->count(),
            'avg_rating' => round((float) CustomerFeedback::avg('rating'), 1),
            'privacy_open' => PrivacyRequest::whereNotIn('status',['completed','rejected'])->count(),
        ]]);
    }
    public function customer360(Customer $customer)
    {
        $this->check('portal.staff');
        return view('portal::customer360', ['data' => $this->svc->customer360($customer)]);
    }
    public function tickets()
    {
        $this->check('portal.staff');
        return view('portal::desk-tickets', ['tickets' => Ticket::where('source','customer_portal')->with('customer')->latest()->paginate(20)]);
    }
    public function ticketReply(Request $r, Ticket $ticket)
    {
        $this->check('portal.staff');
        $d = $r->validate(['body' => 'required|string', 'internal' => 'nullable|boolean']);
        $this->svc->ticketReply($ticket, $d['body'], $r->user()->id, (bool) ($d['internal'] ?? false));
        return back()->with('success', 'Reply added.');
    }
    public function complaints()
    {
        $this->check('complaints.investigate');
        return view('portal::desk-complaints', ['complaints' => Complaint::with('customer')->latest()->paginate(20)]);
    }
    public function complaintTransition(Request $r, Complaint $complaint)
    {
        $this->check('complaints.investigate');
        $d = $r->validate(['to' => 'required|in:' . implode(',', Complaint::STATUSES), 'note' => 'nullable|string']);
        $this->svc->transitionComplaint($complaint, $d['to'], $r->user()->id, $d['note'] ?? null);
        return back()->with('success', 'Complaint updated.');
    }
    public function claims()
    {
        $this->check('claims.view');
        return view('portal::desk-claims', ['claims' => CargoClaim::with('customer')->latest()->paginate(20)]);
    }
    public function claimShow(CargoClaim $claim)
    {
        $this->check('claims.view');
        return view('portal::claim-show', ['c' => $claim->load('events.user','customer','investigator','decider')]);
    }
    public function investigate(Request $r, CargoClaim $claim)
    {
        $this->check('claims.investigate');
        $this->svc->investigateClaim($claim, $r->user()->id, $r->validate(['notes' => 'required|string'])['notes']);
        return back()->with('success', 'Investigation submitted for approval.');
    }
    public function decide(Request $r, CargoClaim $claim)
    {
        $this->check('claims.approve');
        $d = $r->validate(['decision' => 'required|in:approved,partially_approved,rejected',
            'reason' => 'required|string', 'amount' => 'nullable|numeric|min:0']);
        $this->svc->decideClaim($claim, $d['decision'], $r->user()->id, $d['reason'], $d['amount'] ?? null);
        return back()->with('success', 'Decision recorded.');
    }
    public function settle(Request $r, CargoClaim $claim)
    {
        $this->check('claims.approve');
        if ($r->input('settlement_ref')) {
            $this->svc->markSettled($claim, $r->input('settlement_ref'), $r->user()->id);
        } else {
            $this->svc->requestSettlement($claim, $r->user()->id);
        }
        return back()->with('success', 'Settlement updated.');
    }
    public function privacy()
    {
        $this->check('privacy.manage');
        return view('portal::desk-privacy', ['requests' => PrivacyRequest::with('customer')->latest()->paginate(20)]);
    }
}
