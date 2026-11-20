<?php

namespace Modules\Reception\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Department;
use Modules\Reception\Models\AccessRequest;
use Modules\Reception\Services\AccessRequestService;

/** Public, unauthenticated access-request portal. */
class PublicAccessController extends Controller
{
    public function __construct(private AccessRequestService $requests) {}

    public function landing()
    {
        return view('reception::access.public.landing');
    }

    public function form(string $type)
    {
        abort_unless(in_array($type, AccessRequestService::TYPES, true), 404);
        return view('reception::access.public.form', [
            'type' => $type,
            'branches' => Branch::active()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::where('status', 'active')->with('branch:id,code')->orderBy('name')->get(['id', 'name', 'branch_id']),
        ]);
    }

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
            'requested_modules.*' => 'string|max:60',
            'justification' => 'nullable|string|max:2000',
            'privacy_ack' => 'accepted',
            'documents' => 'nullable|array|max:5',
            'documents.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png',
        ]);

        $req = $this->requests->submit($data, $request->ip());
        if ($request->hasFile('documents')) {
            $this->requests->attachDocuments($req, $request->file('documents'), null);
        }
        return redirect()->route('access.submitted', $req->tracking_token)
            ->with('reference', $req->reference);
    }

    public function submitted(string $token)
    {
        $request = AccessRequest::where('tracking_token', $token)->firstOrFail();
        return view('reception::access.public.submitted', ['request' => $request]);
    }

    public function track(Request $request)
    {
        $found = null;
        if ($request->filled(['reference', 'token'])) {
            $found = $this->requests->findForTracking(
                $request->input('reference'), $request->input('token'));
        }
        return view('reception::access.public.track', [
            'found' => $found,
            'searched' => $request->filled(['reference', 'token']),
        ]);
    }
}
