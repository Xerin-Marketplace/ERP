<?php

namespace Modules\Identity\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Identity\Services\AssignmentService;
use Modules\Identity\Services\InvitationService;
use Modules\Identity\Services\UserLifecycleService;
use Modules\Identity\Notifications\AccountActivationInvitation;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function __construct(
        private UserLifecycleService $lifecycle,
        private AssignmentService $assignments,
        private InvitationService $invitations,
    ) {}

    public function index(Request $request)
    {
        $adminBranch = app(\Modules\Identity\Services\AdminDelegationService::class)
            ->adminBranchId($request->user());
        $scoped = ! $request->user()->hasRole('super-admin') && $adminBranch !== null;

        $users = User::with('branch:id,name', 'profile')
            ->when($scoped, fn ($q) => $q->where('branch_id', $adminBranch))
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $s = $request->input('search');
                $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('role'), fn ($q) => $q->role($request->input('role')))
            ->latest()
            ->paginate(15)->withQueryString();

        return view('identity::users.index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Admin-initiated user creation with activation invitation. */
    public function create()
    {
        $guard = app(\Modules\Identity\Services\RoleAssignmentGuard::class);

        return view('identity::users.create', [
            'roles' => $guard->assignableRoles(request()->user(), Role::orderBy('name')->get()),
            'branches' => \Modules\Organization\Models\Branch::active()->orderBy('name')->get(['id', 'name']),
            'companies' => \Modules\Organization\Models\Company::active()->orderBy('legal_name')->get(['id', 'legal_name']),
            'departments' => \Modules\Organization\Models\Department::where('status', 'active')->with('branch:id,code')->orderBy('name')->get(['id', 'name', 'branch_id']),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('users.create'), 403);
        abort_unless($request->user()->can('users.invite'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'role' => ['nullable', 'exists:roles,name'],
            'activation_mode' => ['sometimes', 'in:invite,manual,generate'],
        ]);

        if ($data['role'] ?? null) {
            app(\Modules\Identity\Services\RoleAssignmentGuard::class)
                ->assertCanAssign($request->user(), $data['role'], new User);
        }
        if ($data['branch_id'] ?? null) {
            $branch = \Modules\Organization\Models\Branch::findOrFail($data['branch_id']);
            abort_if($branch->status !== \Modules\Organization\Enums\EntityStatus::Active,
                422, 'Cannot assign users to an inactive branch.');
        }

        $user = \DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'password' => \Hash::make(\Str::random(40)),
                'status' => \App\Enums\AccountStatus::Pending,
            ]);
            if ($data['role'] ?? null) {
                $user->assignRole($data['role']);
            }
            if (($data['company_id'] ?? null) || ($data['department_id'] ?? null)) {
                $branch = $data['branch_id'] ?? null ? \Modules\Organization\Models\Branch::find($data['branch_id']) : null;
                $companyId = $data['company_id'] ?? $branch?->company_id
                    ?? \Modules\Organization\Models\Department::find($data['department_id'] ?? 0)?->company_id;
                if ($companyId) {
                    $placement = $this->assignments->validatePlacement(
                        $companyId, $data['branch_id'] ?? null, $data['department_id'] ?? null);
                    $this->assignments->assign($user, $placement, $request->user()->id, type: 'new');
                }
            }

            if (($data['activation_mode'] ?? 'invite') === 'invite') {
                ['token' => $token] = $this->invitations->generate($user, null, $request->user()->id);
                $user->notify(new AccountActivationInvitation($token));
                app(\App\Services\AuditService::class)->log('user.invited', $user,
                    new: ['email' => $user->email, 'role' => $data['role'] ?? null]);
            } else {
                app(\App\Services\AuditService::class)->log('user.created_manual', $user,
                    new: ['email' => $user->email, 'mode' => $data['activation_mode']]);
            }

            return $user;
        });

        if (($data['activation_mode'] ?? 'invite') === 'generate') {
            $plain = \Str::password(12);
            $user->update(['password' => \Hash::make($plain), 'must_change_password' => true]);
            return redirect()->route('identity.users.show', $user)
                ->with('generated_password', $plain)
                ->with('success', 'User created — temporary password shown below. Verify & activate when ready.');
        }

        return redirect()->route('identity.users.show', $user)->with('success',
            ($data['activation_mode'] ?? 'invite') === 'invite'
                ? "Invitation sent to {$user->email}."
                : "User created as pending — verify & activate from this page.");
    }

    /** Admin marks the email verified and activates the account — no email link needed. */
    public function verifyActivate(User $user, Request $request)
    {
        abort_unless($request->user()->can('users.activate'), 403);
        $this->lifecycle->verifyAndActivate($user, $request->user()->id);
        return back()->with('success', "{$user->email} verified & activated.");
    }

    /** Soft-delete a user (self & super-admin protected). */
    public function destroy(User $user, Request $request)
    {
        abort_unless($request->user()->can('users.delete'), 403);
        $this->lifecycle->delete($user, $request->user()->id, $request->input('reason'));
        return redirect()->route('identity.users.index')
            ->with('success', "Account {$user->email} deleted.");
    }

    /** Admin generates a temporary password; user is forced to change it on next login. */
    public function generatePassword(User $user, Request $request)
    {
        abort_unless($request->user()->can('users.reset_password'), 403);
        abort_if($user->hasRole('super-admin'), 422, 'Cannot set a password for super-admin.');

        $plain = \Str::password(12);
        $user->update(['password' => \Hash::make($plain), 'must_change_password' => true]);
        $this->lifecycle->revokeAccess($user, $request->user()->id, 'password_generated');
        app(\App\Services\AuditService::class)->log('user.password_generated', $user,
            metadata: ['actor_id' => $request->user()->id]);

        return back()->with('generated_password', $plain)
            ->with('success', 'Temporary password generated — user must change it on first login.');
    }

    /** Admin-triggered password reset via a fresh activation link. */
    public function resetPassword(User $user, Request $request)
    {
        abort_unless($request->user()->can('users.reset_password'), 403);

        ['token' => $token] = $this->invitations->generate($user, null, $request->user()->id);
        $user->notify(new AccountActivationInvitation($token));

        app(\App\Services\AuditService::class)->log('user.password_reset_sent', $user);

        return back()->with('success', 'Password reset link sent.');
    }

    public function show(User $user)
    {
        $user->load([
            'profile', 'branch', 'roles.permissions',
            'assignments.company:id,legal_name', 'assignments.branch:id,name',
            'assignments.department:id,name', 'assignments.manager:id,name',
            'assignments.assignedBy:id,name',
            'loginHistories' => fn ($q) => $q->limit(15),
            'securityEvents' => fn ($q) => $q->limit(15),
            'registrationApplication',
        ]);

        return view('identity::users.show', [
            'user' => $user,
            'roles' => Role::whereNotIn('name', \Modules\Identity\Database\Seeders\IdentityDatabaseSeeder::PROTECTED_ROLES)->orderBy('name')->get(['id', 'name']),
            'pendingInvitation' => \Modules\Identity\Models\AccessInvitation::where('user_id', $user->id)
                ->whereNull('used_at')->whereNull('revoked_at')->where('expires_at', '>', now())->latest()->first(),
            'companies' => \Modules\Organization\Models\Company::where('status', 'active')->orderBy('legal_name')->get(['id', 'legal_name']),
            'branches' => \Modules\Organization\Models\Branch::where('status', 'active')->orderBy('name')->get(['id', 'name', 'company_id']),
            'departments' => \Modules\Organization\Models\Department::where('status', 'active')->with('branch:id,code')->orderBy('name')->get(['id', 'name', 'company_id', 'branch_id']),
            'managers' => User::where('id', '!=', $user->id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(User $user, Request $request)
    {
        abort_unless($request->user()->can('users.update'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'employee_number' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:150'],
        ]);

        $user->update(collect($data)->only(['name', 'phone'])->toArray());
        $user->profile()->updateOrCreate(['user_id' => $user->id],
            collect($data)->only(['employee_number', 'job_title'])->toArray());

        app(\App\Services\AuditService::class)->log('users.updated', $user, new: $data);

        return back()->with('success', 'User updated.');
    }

    public function assignRole(User $user, Request $request)
    {
        abort_unless($request->user()->can('roles.assign'), 403);
        $request->validate(['role' => ['required', 'exists:roles,name']]);
        abort_if($user->id === $request->user()->id && ! $request->user()->hasRole('super-admin'),
            422, 'You cannot change your own role.');
        $delegation = app(\Modules\Identity\Services\AdminDelegationService::class);
        $delegation->assertCanAssignRole($request->user(), $request->input('role'), $user);
        if ($user->hasRole('super-admin') && $request->input('role') !== 'super-admin') {
            $delegation->assertNotLastSuperAdmin($user);
        }

        $old = $user->getRoleNames()->toArray();
        $user->syncRoles([$request->input('role')]);

        app(\App\Services\AuditService::class)->log('users.role_changed', $user,
            old: ['roles' => $old], new: ['roles' => [$request->input('role')]]);

        return back()->with('success', "Role updated to {$request->input('role')}.");
    }

    public function suspend(User $user, Request $request)
    {
        abort_unless($request->user()->can('users.suspend'), 403);
        abort_if($user->id === $request->user()->id, 422, 'You cannot suspend your own account.');
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $this->lifecycle->suspend($user, $request->user()->id, $request->input('reason'));

        return back()->with('success', "{$user->name} suspended — sessions and tokens revoked.");
    }

    public function activate(User $user, Request $request)
    {
        abort_unless($request->user()->can('users.activate'), 403);
        $this->lifecycle->activate($user, $request->user()->id);

        return back()->with('success', "{$user->name} activated.");
    }

    public function deactivate(User $user, Request $request)
    {
        abort_unless($request->user()->can('users.deactivate'), 403);
        abort_if($user->id === $request->user()->id, 422, 'You cannot deactivate your own account.');
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $this->lifecycle->deactivate($user, $request->user()->id, $request->input('reason'));

        return back()->with('success', "{$user->name} deactivated.");
    }

    public function revokeSessions(User $user, Request $request)
    {
        abort_unless($request->user()->can('users.sessions.revoke'), 403);
        $this->lifecycle->revokeAccess($user, $request->user()->id, 'manual_revocation');

        return back()->with('success', 'All sessions and API tokens revoked.');
    }

    public function resendInvitation(User $user, Request $request)
    {
        abort_unless($request->user()->can('registration.provision'), 403);
        abort_if($user->status === \App\Enums\AccountStatus::Active, 422, 'User is already active.');

        ['token' => $token] = $this->invitations->generate($user, $user->registrationApplication, $request->user()->id);
        $user->notify(new AccountActivationInvitation($token));

        app(\App\Services\AuditService::class)->log('invitation.resent', $user);

        return back()->with('success', 'Activation invitation resent.');
    }

    /* ---------------------- Assignment / transfers ---------------------- */

    public function reassign(User $user, Request $request)
    {
        abort_unless($request->user()->can('users.assignments.manage'), 403);

        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position' => ['nullable', 'string', 'max:150'],
            'manager_user_id' => ['nullable', 'exists:users,id'],
            'assignment_type' => ['required', 'in:transfer,position_change,temporary,termination,new'],
            'effective_start' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $placement = $this->assignments->validatePlacement(
            $data['company_id'], $data['branch_id'] ?? null, $data['department_id'] ?? null
        );

        $this->assignments->assign(
            $user, $placement, $request->user()->id,
            type: $data['assignment_type'],
            position: $data['position'] ?? null,
            managerId: $data['manager_user_id'] ?? null,
            reason: $data['reason'] ?? null,
            effectiveStart: $data['effective_start'],
            status: $data['assignment_type'] === 'temporary' ? 'temporary' : 'active',
        );

        return back()->with('success', 'Organizational assignment recorded.');
    }
}
