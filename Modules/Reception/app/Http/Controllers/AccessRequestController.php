<?php

namespace Modules\Reception\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Reception\Models\AccessRequest;
use Modules\Reception\Models\AccessRequestDocument;
use Modules\Reception\Services\AccessDashboardService;
use Modules\Reception\Services\AccessRequestService;
use Modules\Reception\Services\VerificationService;

class AccessRequestController extends Controller
{
    public function __construct(
        private AccessRequestService $requests,
        private AccessDashboardService $dashboard,
        private VerificationService $verifications,
    ) {}

    public function dashboard(Request $r)
    {
        return view('reception::access.dashboard', [
            'metrics' => $this->dashboard->metrics(),
            'recent' => AccessRequest::with('branch')->latest('submitted_at')->limit(10)->get(),
        ]);
    }

    public function index(Request $r)
    {
        $requests = AccessRequest::with('branch', 'department')
            ->when($r->type, fn ($q) => $q->where('type', $r->type))
            ->when($r->status, fn ($q) => $q->where('status', $r->status))
            ->when($r->search, fn ($q) => $q->where(fn ($x) => $x
                ->where('reference', 'like', "%{$r->search}%")
                ->orWhere('full_name', 'like', "%{$r->search}%")
                ->orWhere('email', 'like', "%{$r->search}%")))
            ->latest('submitted_at')->paginate(25)->withQueryString();
        return view('reception::access.index', compact('requests'));
    }

    public function show(AccessRequest $accessRequest)
    {
        $accessRequest->load('verifications', 'tasks.stage', 'history.actor',
            'documents.file', 'workflowVersion.stages', 'employee.user', 'sponsor');
        app(VerificationService::class)->bootstrap($accessRequest);
        return view('reception::access.show', ['request' => $accessRequest]);
    }

    public function uploadDocument(Request $r, AccessRequest $accessRequest)
    {
        $data = $r->validate([
            'documents' => 'required|array|max:5',
            'documents.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png',
            'category' => 'nullable|string|max:40',
        ]);
        $n = $this->requests->attachDocuments($accessRequest, $r->file('documents'),
            $r->user()->id, $data['category'] ?? 'supporting');
        return back()->with('success', "{$n} document(s) uploaded.");
    }

    public function downloadDocument(AccessRequest $accessRequest, AccessRequestDocument $document)
    {
        abort_unless($document->request_id === $accessRequest->id, 404);
        return app(\App\Services\FileService::class)->download($document->file);
    }

    public function startReview(Request $r, AccessRequest $accessRequest)
    {
        $this->requests->startReview($accessRequest, $r->user()->id);
        return back()->with('success', 'Review started.');
    }

    public function requestInfo(Request $r, AccessRequest $accessRequest)
    {
        $data = $r->validate(['note' => 'required|string|max:1000']);
        $this->requests->requestInfo($accessRequest, $r->user()->id, $data['note']);
        return back()->with('success', 'Applicant notified for more information.');
    }

    public function verify(Request $r, AccessRequest $accessRequest, int $verification)
    {
        $data = $r->validate([
            'status' => 'required|in:verified,failed,info_required,not_applicable',
            'notes' => 'nullable|string|max:1000',
        ]);
        $check = $accessRequest->verifications()->findOrFail($verification);
        app(VerificationService::class)->record($check, $r->user()->id, $data['status'], $data['notes'] ?? null);
        return back()->with('success', 'Verification recorded.');
    }

    public function route(Request $r, AccessRequest $accessRequest)
    {
        $this->requests->route($accessRequest, $r->user()->id);
        return back()->with('success', 'Request routed into the approval workflow.');
    }

    public function reject(Request $r, AccessRequest $accessRequest)
    {
        $data = $r->validate(['reason' => 'required|string|max:1000']);
        $this->requests->reject($accessRequest, $r->user()->id, $data['reason']);
        return back()->with('success', 'Request rejected.');
    }

    public function note(Request $r, AccessRequest $accessRequest)
    {
        $data = $r->validate(['note' => 'required|string|max:1000']);
        $this->requests->addInternalNote($accessRequest, $r->user()->id, $data['note']);
        return back()->with('success', 'Note added.');
    }
}
