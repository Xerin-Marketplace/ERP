<?php

namespace Modules\Identity\Http\Controllers;

use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Identity\Enums\ApplicationStatus;
use Modules\Identity\Models\RegistrationApplication;
use Modules\Identity\Models\RegistrationDocument;
use Modules\Identity\Services\ProvisioningService;
use Modules\Identity\Services\RegistrationService;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;
use Spatie\Permission\Models\Role;

class RegistrationAdminController extends Controller
{
    public function __construct(
        private RegistrationService $registrations,
        private ProvisioningService $provisioning,
        private FileService $files,
    ) {}

    public function index(Request $request)
    {
        $applications = RegistrationApplication::with('requestedCompany:id,legal_name', 'reviewer:id,name')
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $s = $request->input('search');
                $q->where('reference', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%");
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
            ->latest()
            ->paginate(15)->withQueryString();

        return view('identity::registrations.index', [
            'applications' => $applications,
            'stats' => $this->registrations->dashboardStats(),
            'statuses' => ApplicationStatus::cases(),
        ]);
    }

    public function show(RegistrationApplication $application)
    {
        $application->load([
            'documents.file', 'statusHistories.actor:id,name', 'reviews.reviewer:id,name',
            'requestedCompany', 'requestedBranch', 'requestedDepartment', 'reviewer:id,name',
        ]);

        return view('identity::registrations.show', [
            'application' => $application,
            'statuses' => ApplicationStatus::cases(),
            'companies' => Company::where('status', 'active')->orderBy('legal_name')->get(['id', 'legal_name']),
            'branches' => Branch::where('status', 'active')->orderBy('name')->get(['id', 'name', 'company_id']),
            'departments' => Department::where('status', 'active')->orderBy('name')->get(['id', 'name', 'company_id', 'branch_id']),
            'roles' => Role::where('guard_name', 'web')->whereNotIn('name', \Modules\Identity\Database\Seeders\IdentityDatabaseSeeder::PROTECTED_ROLES)->orderBy('name')->get(['id', 'name']),
            'managers' => \App\Models\User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /* ---------------------------- Workflow ---------------------------- */

    public function startReview(RegistrationApplication $application, Request $request)
    {
        $this->registrations->transition(
            $application,
            in_array($application->status, [ApplicationStatus::Submitted], true)
                ? ApplicationStatus::PendingReview : ApplicationStatus::UnderReview,
            $request->user()->id,
        );

        return back()->with('success', 'Application moved to review queue.');
    }

    public function addNote(RegistrationApplication $application, Request $request)
    {
        $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $this->registrations->addInternalNote($application, $request->user()->id, $request->input('note'));

        return back()->with('success', 'Internal note added.');
    }

    public function requestInformation(RegistrationApplication $application, Request $request)
    {
        $request->validate(['message' => ['required', 'string', 'max:2000']]);
        $this->registrations->requestInformation($application, $request->user()->id, $request->input('message'));

        return back()->with('success', 'Information request sent to the applicant.');
    }

    public function approve(RegistrationApplication $application, Request $request)
    {
        abort_unless($request->user()->can('registration.approve'), 403);
        $this->registrations->approve($application, $request->user()->id);

        return back()->with('success', 'Application approved. Proceed to assignment and provisioning.');
    }

    public function reject(RegistrationApplication $application, Request $request)
    {
        abort_unless($request->user()->can('registration.reject'), 403);
        $request->validate(['reason' => ['required', 'string', 'min:5', 'max:2000']]);
        $this->registrations->reject($application, $request->user()->id, $request->input('reason'));

        return back()->with('success', 'Application rejected.');
    }

    /* --------------------------- Provisioning -------------------------- */

    public function provision(RegistrationApplication $application, Request $request)
    {
        abort_unless($request->user()->can('registration.provision'), 403);

        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position' => ['nullable', 'string', 'max:150'],
            'manager_user_id' => ['nullable', 'exists:users,id'],
            'employee_number' => ['nullable', 'string', 'max:50'],
            'employment_type' => ['nullable', 'string', 'max:50'],
            'effective_start' => ['nullable', 'date'],
            'role' => ['required', 'exists:roles,name'],
        ]);

        // No privileged self-service: only role.assign holders may grant roles,
        // and protected roles are never provisionable.
        abort_if(in_array($data['role'], \Modules\Identity\Database\Seeders\IdentityDatabaseSeeder::PROTECTED_ROLES, true),
            403, 'This role cannot be granted through provisioning.');

        ['user' => $user] = $this->provisioning->provision($application, $data, $data['role'], $request->user()->id);

        return redirect()->route('identity.users.show', $user)
            ->with('success', "Account provisioned for {$user->name}. Activation invitation sent.");
    }

    /* ---------------------------- Documents ---------------------------- */

    public function downloadDocument(RegistrationDocument $document, Request $request)
    {
        abort_unless($request->user()->can('registration.review'), 403);

        return $this->files->download($document->file);
    }
}
