<?php

namespace Modules\Reception\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Reception\Models\AccessApprovalTask;
use Modules\Reception\Models\AccessRequest;
use Modules\Reception\Services\AccessProvisioningService;
use Modules\Reception\Services\AccessRequestService;
use Modules\Reception\Services\ApprovalTaskService;

class AccessApiController extends Controller
{
    public function __construct(
        private AccessRequestService $requests,
        private ApprovalTaskService $tasks,
        private AccessProvisioningService $provisioning,
    ) {}

    /** POST /api/v1/access-requests — public intake, throttled. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:new_employee,existing_employee,external',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'organization' => 'nullable|string|max:255',
            'branch_id' => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'job_title' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|max:40',
            'employee_id' => 'nullable|exists:employees,id',
            'sponsor_id' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:today',
            'requested_modules' => 'nullable|array',
            'justification' => 'nullable|string|max:2000',
        ]);
        $req = $this->requests->submit($data, $request->ip());
        return response()->json([
            'reference' => $req->reference,
            'tracking_token' => $req->tracking_token,
            'status' => $req->status,
        ], 201);
    }

    /** GET /api/v1/access-requests/track — public status check. */
    public function track(Request $request)
    {
        $request->validate(['reference' => 'required|string', 'token' => 'required|string']);
        $req = $this->requests->findForTracking($request->input('reference'), $request->input('token'));
        abort_unless($req, 404, 'Request not found — check reference and token.');
        return response()->json([
            'reference' => $req->reference, 'type' => $req->type,
            'status' => $req->status, 'submitted_at' => $req->submitted_at,
            'history' => $req->history()->where('internal', false)->latest()->get()
                ->map(fn ($h) => ['event' => $h->event, 'at' => $h->created_at]),
        ]);
    }

    /** GET /api/v1/access-requests — internal list. */
    public function index(Request $request)
    {
        return AccessRequest::with('branch', 'department')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->latest('submitted_at')->paginate(25);
    }

    public function show(AccessRequest $accessRequest)
    {
        return $accessRequest->load('verifications', 'tasks', 'history', 'workflowVersion.stages');
    }

    public function route(Request $request, AccessRequest $accessRequest)
    {
        $this->requests->route($accessRequest, $request->user()->id);
        return response()->json(['status' => $accessRequest->fresh()->status]);
    }

    public function reject(Request $request, AccessRequest $accessRequest)
    {
        $data = $request->validate(['reason' => 'required|string|max:1000']);
        $this->requests->reject($accessRequest, $request->user()->id, $data['reason']);
        return response()->json(['status' => 'rejected']);
    }

    public function decide(Request $request, AccessApprovalTask $task)
    {
        $data = $request->validate([
            'decision' => 'required|in:approved,rejected,changes_requested',
            'comment' => 'nullable|string|max:1000',
        ]);
        $this->tasks->decide($task, $request->user(), $data['decision'], $data['comment'] ?? null);
        return response()->json(['task' => $task->fresh()->status, 'request' => $task->request->fresh()->status]);
    }

    public function provision(Request $request, AccessRequest $accessRequest)
    {
        $data = $request->validate(['roles' => 'nullable|array', 'roles.*' => 'exists:roles,name']);
        $user = $this->provisioning->provision($accessRequest, $data, $request->user());
        return response()->json(['user_id' => $user->id, 'status' => $accessRequest->fresh()->status]);
    }
}
