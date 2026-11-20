<?php

namespace Modules\Organization\Policies;

use App\Models\User;
use Modules\Organization\Models\Department;
use Modules\Organization\Support\BranchScope;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('organization.departments.view');
    }

    public function view(User $user, Department $department): bool
    {
        return $user->can('organization.departments.view')
            && (! $department->branch_id || BranchScope::canSee($user, $department->branch_id));
    }

    public function create(User $user): bool
    {
        return $user->can('organization.departments.create');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->can('organization.departments.update')
            && (! $department->branch_id || BranchScope::canSee($user, $department->branch_id));
    }

    public function deactivate(User $user, Department $department): bool
    {
        return $user->can('organization.departments.deactivate')
            && (! $department->branch_id || BranchScope::canSee($user, $department->branch_id));
    }
}
