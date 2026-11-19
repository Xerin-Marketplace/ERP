<?php

namespace Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Ticketing\Models\TicketApproval;
use Modules\Ticketing\Services\ApprovalService;

class ApprovalController extends Controller
{
    public function __construct(private ApprovalService $approvals) {}

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->can('ticket.approve') || TicketApproval::pending()->where('approver_id', $user->id)->exists(), 403);

        $pending = TicketApproval::pending()->with('ticket.requester:id,name', 'ticket.type:id,name')
            ->get()->filter(fn ($a) => $this->approvals->canDecide($user, $a));

        return view('ticketing::approvals.index', ['pending' => $pending]);
    }

    public function decide(Request $request, TicketApproval $approval)
    {
        $data = $request->validate(['decision' => 'required|in:approved,rejected', 'note' => 'nullable|string']);
        $this->approvals->decide($approval, $request->user(), $data['decision'], $data['note'] ?? null);

        return back()->with('success', 'Decision recorded.');
    }
}
