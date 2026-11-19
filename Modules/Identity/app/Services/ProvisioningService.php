<?php

namespace Modules\Identity\Services;

use App\Enums\AccountStatus;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Enums\ApplicationStatus;
use Modules\Identity\Models\AccessInvitation;
use Modules\Identity\Models\RegistrationApplication;
use Modules\Identity\Notifications\AccountActivationInvitation;
use Spatie\Permission\Models\Role;

/**
 * Turn an approved application + organizational assignment into a
 * provisioned (pending-activation) user account — one transaction,
 * no partially-configured accounts on failure.
 */
class ProvisioningService
{
    public function __construct(
        private AuditService $audit,
        private AssignmentService $assignments,
        private InvitationService $invitations,
    ) {}

    /**
     * Provision a user account from an approved application.
     *
     * @param  array  $placement  company_id / branch_id / department_id / position / manager_user_id / employee_number
     * @return array{user: User, token: string}
     */
    public function provision(RegistrationApplication $application, array $placement, string $role, int $actorId): array
    {
        abort_unless($application->status === ApplicationStatus::Approved, 422,
            'Application must be approved before provisioning.');
        abort_if($application->provisioned_user_id, 422, 'Application already provisioned.');

        $roleModel = Role::where('name', $role)->where('guard_name', 'web')->firstOrFail();
        abort_if($roleModel->name === 'super-admin', 403,
            'Super Administrator cannot be granted through registration provisioning.');

        $validPlacement = $this->assignments->validatePlacement(
            $placement['company_id'] ?? null,
            $placement['branch_id'] ?? null,
            $placement['department_id'] ?? null,
        );

        if (! empty($placement['employee_number'])
            && \Modules\Identity\Models\UserProfile::where('employee_number', $placement['employee_number'])->exists()) {
            throw ValidationException::withMessages(['employee_number' => 'Employee number is already in use.']);
        }

        return DB::transaction(function () use ($application, $placement, $validPlacement, $roleModel, $actorId) {
            // 1. User account — pending status, unusable random password.
            $user = User::create([
                'name' => $application->full_name,
                'email' => $application->email,
                'phone' => $application->phone,
                'branch_id' => $validPlacement['branch_id'],
                'password' => Hash::make(Str::random(40)),
                'status' => AccountStatus::Pending,
            ]);

            // 2. Extended profile
            $user->profile()->create([
                'first_name' => $application->first_name,
                'middle_name' => $application->middle_name,
                'last_name' => $application->last_name,
                'employee_number' => $placement['employee_number'] ?? $this->nextEmployeeNumber(),
                'job_title' => $placement['position'] ?? $application->job_title,
                'employment_type' => $placement['employment_type'] ?? $application->employment_type,
                'alt_phone' => $application->alt_phone,
                'date_of_birth' => $application->date_of_birth,
                'residential_address' => $application->residential_address,
                'photo_path' => $application->photo_path,
            ]);

            // 3. Organizational assignment (history record #1)
            $this->assignments->assign(
                $user,
                $validPlacement,
                $actorId,
                type: 'new',
                position: $placement['position'] ?? $application->job_title,
                managerId: $placement['manager_user_id'] ?? null,
                effectiveStart: $placement['effective_start'] ?? null,
            );

            // 4. Role — applicant can never self-assign; IT chose it explicitly.
            $user->assignRole($roleModel);

            // 5. Application state
            $application->update([
                'provisioned_user_id' => $user->id,
                'status' => ApplicationStatus::Provisioning,
            ]);

            // 6. Activation invitation
            ['invitation' => $invitation, 'token' => $token] =
                $this->invitations->generate($user, $application, $actorId);

            $user->notify(new AccountActivationInvitation($token));

            $this->audit->log('provisioning.completed', $user, new: [
                'application' => $application->reference,
                'role' => $roleModel->name,
                'company_id' => $validPlacement['company_id'],
            ]);

            return ['user' => $user, 'token' => $token, 'invitation' => $invitation];
        });
    }

    /** Provisioning succeeded + user activated → application becomes Active. */
    public function markActivated(RegistrationApplication $application): void
    {
        $application->update(['status' => ApplicationStatus::Active]);
    }

    private function nextEmployeeNumber(): string
    {
        $max = \Modules\Identity\Models\UserProfile::whereNotNull('employee_number')
            ->where('employee_number', 'like', 'XER-%')
            ->lockForUpdate()
            ->pluck('employee_number')
            ->map(fn ($n) => (int) substr($n, 4))
            ->max() ?? 0;

        return 'XER-'.str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);
    }
}
