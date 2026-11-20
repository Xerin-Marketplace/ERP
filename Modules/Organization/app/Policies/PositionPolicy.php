<?php

namespace Modules\Organization\Policies;

use App\Models\User;
use Modules\Employee\Models\JobPosition;

/**
 * Positions are company-level job titles (job_positions table, Employee module).
 * They are organizational data, not RBAC roles — managed by org permissions.
 */
class PositionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('organization.positions.view');
    }

    public function view(User $user, JobPosition $position): bool
    {
        return $user->can('organization.positions.view');
    }

    public function create(User $user): bool
    {
        return $user->can('organization.positions.create');
    }

    public function update(User $user, JobPosition $position): bool
    {
        return $user->can('organization.positions.update');
    }

    public function manage(User $user, JobPosition $position): bool
    {
        return $user->can('organization.positions.manage');
    }
}
