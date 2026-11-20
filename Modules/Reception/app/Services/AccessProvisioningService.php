<?php

namespace Modules\Reception\Services;

use App\Enums\AccountStatus;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Services\InvitationService;
use Modules\Identity\Services\RoleAssignmentGuard;
use Modules\Reception\Models\AccessRequest;
use Modules\Reception\Models\ExternalUserProfile;

/**
 * Turns an approved request into a live account (or account change).
 * Delegates to Identity's InvitationService + RoleAssignmentGuard — never
 * bypasses them.
 */
class AccessProvisioningService
{
    public function __construct(
        private InvitationService $invitations,
        private RoleAssignmentGuard $roleGuard,
        private AccessRequestService $requests,
        private AuditService $audit,
    ) {}

    /**
     * Provision an approved request.
     * $config: roles[], org assignment overrides, account_expiry, sponsor.
     * Idempotent: already-provisioned requests short-circuit.
     */
    public function provision(AccessRequest $request, array $config, User $admin): User
    {
        if ($request->status !== 'approved') {
            throw ValidationException::withMessages(['request' =>
                "Only approved requests can be provisioned (current: {$request->status})."]);
        }
        if ($request->provisioned_user_id) {
            return $request->provisionedUser; // retry-safe
        }

        return DB::transaction(function () use ($request, $config, $admin) {
            $request = AccessRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($request->provisioned_user_id) return $request->provisionedUser;

            $this->requests->markProvisioning($request, $admin->id);

            $user = match ($request->type) {
                'new_employee'       => $this->provisionNew($request, $config, $admin),
                'existing_employee'  => $this->provisionExisting($request, $config, $admin),
                'external'           => $this->provisionExternal($request, $config, $admin),
                default              => throw ValidationException::withMessages(['type' => 'Unknown type.']),
            };

            // Apply role assignments inside the admin's delegation scope.
            foreach ($config['roles'] ?? [] as $role) {
                $this->roleGuard->assertCanAssign($admin, $role, $user);
            }
            if ($roles = $config['roles'] ?? null) {
                $user->syncRoles($roles);
                $this->requests->record($request, 'provisioning.roles_assigned',
                    null, null, ['roles' => $roles], true, null, $admin->id);
            }

            $this->requests->markProvisioned($request, $admin->id, $user->id);
            $this->audit->log('access.provisioned', $request,
                new: ['user_id' => $user->id, 'type' => $request->type]);
            return $user;
        });
    }

    /** New employee: fresh user, pending status, activation invitation. */
    private function provisionNew(AccessRequest $request, array $config, User $admin): User
    {
        if (User::where('email', $request->email)->exists()) {
            throw ValidationException::withMessages(['email' => 'An account with this email already exists.']);
        }
        $user = User::create([
            'name'     => $request->full_name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make(Str::random(40)),
            'status'   => AccountStatus::Pending,
        ]);
        ['token' => $token] = $this->invitations->generate($user, null, $admin->id);
        $user->notify(new \Modules\Identity\Notifications\AccountActivationInvitation($token));
        $this->requests->record($request, 'provisioning.invitation_sent',
            null, null, ['user_id' => $user->id], true, null, $admin->id);
        return $user;
    }

    /** Existing employee: mutate the linked account — never create a second. */
    private function provisionExisting(AccessRequest $request, array $config, User $admin): User
    {
        $employee = $request->employee;
        if (! $employee?->user_id) {
            throw ValidationException::withMessages(['employee' => 'Linked employee has no user account.']);
        }
        $user = $employee->user;

        // Apply approved organizational/access changes from requested_changes.
        $changes = $request->requested_changes ?? [];
        if (($changes['type'] ?? null) === 'temporary_access' && ! empty($changes['expires_on'])) {
            $this->requests->record($request, 'provisioning.temporary_access',
                null, null, ['expires_on' => $changes['expires_on']], true, null, $admin->id);
        }
        return $user;
    }

    /** External: time-boxed account + sponsor + mandatory expiry. */
    private function provisionExternal(AccessRequest $request, array $config, User $admin): User
    {
        abort_if(! $request->expiry_date, 422, 'External accounts require an expiry date.');
        abort_if(! $request->sponsor_id, 422, 'External accounts require an internal sponsor.');

        $user = User::where('email', $request->email)->first() ?? User::create([
            'name'     => $request->full_name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make(Str::random(40)),
            'status'   => AccountStatus::Pending,
        ]);

        ExternalUserProfile::updateOrCreate(
            ['user_id' => $user->id],
            ['access_request_id' => $request->id,
             'category' => $config['category'] ?? 'contractor',
             'organization' => $request->organization ?? 'External',
             'sponsor_id' => $request->sponsor_id,
             'expires_on' => $request->expiry_date]
        );

        if ($user->status === AccountStatus::Pending) {
            ['token' => $token] = $this->invitations->generate($user, null, $admin->id);
            $user->notify(new \Modules\Identity\Notifications\AccountActivationInvitation($token));
        }
        return $user;
    }

    /** Queue view: approved + provisioning + provisioned requests. */
    public function queue()
    {
        return AccessRequest::with('branch', 'department', 'employee.user')
            ->whereIn('status', ['approved', 'provisioning', 'provisioned'])
            ->latest('decided_at')->get();
    }
}
