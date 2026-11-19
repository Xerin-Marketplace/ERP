<?php

namespace Modules\Identity\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Enums\ApplicationStatus;
use Modules\Identity\Http\Requests\SubmitRegistrationRequest;
use Modules\Identity\Models\RegistrationApplication;
use Modules\Identity\Services\AssignmentService;
use Modules\Identity\Services\ProvisioningService;
use Modules\Identity\Services\RegistrationService;
use Modules\Identity\Services\UserLifecycleService;
use Spatie\Permission\Models\Role;

class IdentityApiController extends ApiController
{
    public function __construct(
        private RegistrationService $registrations,
        private ProvisioningService $provisioning,
        private AssignmentService $assignments,
        private UserLifecycleService $lifecycle,
    ) {}

    /* ------------------------------ Public ----------------------------- */

    /** POST /api/v1/identity/register — public, rate-limited. */
    public function register(SubmitRegistrationRequest $request)
    {
        $application = $this->registrations->submit($request->validated());

        return ApiResponse::created([
            'reference' => $application->reference,
            'status' => $application->status->value,
        ], 'Application received. Save your reference number to track its status.');
    }

    /** GET /api/v1/identity/registration/status/{reference} — public, rate-limited, requires ?email= */
    public function registrationStatus(string $reference, Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $key = 'api-reg-track:'.strtolower($request->ip());
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return ApiResponse::error('Too many attempts.', 429);
        }
        RateLimiter::hit($key, 300);

        $application = $this->registrations->lookupForApplicant($reference, $request->input('email'));

        if (! $application) {
            return ApiResponse::error('Application not found.', 404);
        }

        return ApiResponse::success([
            'reference' => $application->reference,
            'status' => $application->status->publicLabel(),
            'message' => $application->status === ApplicationStatus::InformationRequested
                ? $application->applicant_message : null,
            'submitted_at' => $application->submitted_at,
        ]);
    }

    /* ------------------------------- Admin ----------------------------- */

    public function adminRegistrations(Request $request)
    {
        $apps = RegistrationApplication::with('requestedCompany:id,legal_name', 'reviewer:id,name')
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $s = $request->input('search');
                $q->where('reference', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%");
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()->paginate(min((int) $request->input('per_page', 15), 100));

        return ApiResponse::paginated($apps);
    }

    public function adminRegistrationShow(RegistrationApplication $application)
    {
        return ApiResponse::success(
            $application->load('documents.file', 'statusHistories.actor:id,name', 'reviews.reviewer:id,name',
                'requestedCompany', 'requestedBranch', 'requestedDepartment')
        );
    }

    public function review(RegistrationApplication $application, Request $request)
    {
        $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $this->registrations->addInternalNote($application, $request->user()->id, $request->input('note'));

        return ApiResponse::success(null, 'Note recorded.');
    }

    public function requestInformation(RegistrationApplication $application, Request $request)
    {
        abort_unless($request->user()->can('registration.request-info'), 403);
        $request->validate(['message' => ['required', 'string', 'max:2000']]);
        $this->registrations->requestInformation($application, $request->user()->id, $request->input('message'));

        return ApiResponse::success(['status' => $application->status->value], 'Information requested.');
    }

    public function approve(RegistrationApplication $application, Request $request)
    {
        abort_unless($request->user()->can('registration.approve'), 403);
        $this->registrations->approve($application, $request->user()->id);

        return ApiResponse::success(['status' => 'approved']);
    }

    public function reject(RegistrationApplication $application, Request $request)
    {
        abort_unless($request->user()->can('registration.reject'), 403);
        $request->validate(['reason' => ['required', 'string', 'min:5', 'max:2000']]);
        $this->registrations->reject($application, $request->user()->id, $request->input('reason'));

        return ApiResponse::success(['status' => 'rejected']);
    }

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
            'effective_start' => ['nullable', 'date'],
            'role' => ['required', 'exists:roles,name'],
        ]);

        abort_if(in_array($data['role'], \Modules\Identity\Database\Seeders\IdentityDatabaseSeeder::PROTECTED_ROLES, true),
            403, 'This role cannot be granted through provisioning.');

        ['user' => $user] = $this->provisioning->provision($application, $data, $data['role'], $request->user()->id);

        return ApiResponse::success(['user_id' => $user->id], 'Account provisioned and invitation sent.');
    }

    /* ------------------------------ Users ------------------------------ */

    public function users(Request $request)
    {
        $users = User::with('profile', 'branch:id,name', 'roles:id,name')
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $s = $request->input('search');
                $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()->paginate(min((int) $request->input('per_page', 15), 100));

        return ApiResponse::paginated($users);
    }

    public function userShow(User $user)
    {
        return ApiResponse::success($user->load(
            'profile', 'roles.permissions', 'currentAssignment.company:id,legal_name',
            'currentAssignment.branch:id,name', 'currentAssignment.department:id,name',
            'assignments', 'loginHistories', 'securityEvents'
        ));
    }

    public function suspendUser(User $user, Request $request)
    {
        abort_unless($request->user()->can('user.suspend'), 403);
        abort_if($user->id === $request->user()->id, 422, 'Cannot suspend your own account.');
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $this->lifecycle->suspend($user, $request->user()->id, $request->input('reason'));

        return ApiResponse::success(['status' => $user->status->value], 'User suspended.');
    }

    public function activateUser(User $user, Request $request)
    {
        abort_unless($request->user()->can('user.activate'), 403);
        $this->lifecycle->activate($user, $request->user()->id);

        return ApiResponse::success(['status' => $user->status->value], 'User activated.');
    }

    public function roles()
    {
        return ApiResponse::success(
            Role::whereNotIn('name', \Modules\Identity\Database\Seeders\IdentityDatabaseSeeder::PROTECTED_ROLES)
                ->with('permissions:id,name')->withCount('users')->get()
        );
    }

    public function me(Request $request)
    {
        return ApiResponse::success($request->user()->load(
            'profile', 'roles.permissions', 'branch.company:id,legal_name',
            'currentAssignment.company:id,legal_name', 'currentAssignment.branch:id,name',
            'currentAssignment.department:id,name'
        ));
    }
}
