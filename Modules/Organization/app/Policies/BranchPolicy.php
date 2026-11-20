<?php

namespace Modules\Organization\Policies;

use App\Models\User;
use Modules\Organization\Models\Branch;
use Modules\Organization\Support\BranchScope;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('organization.branches.view');
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->can('organization.branches.view')
            && BranchScope::canSee($user, $branch->id);
    }

    public function create(User $user): bool
    {
        return $user->can('organization.branches.create');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->can('organization.branches.update')
            && BranchScope::canSee($user, $branch->id);
    }

    public function activate(User $user, Branch $branch): bool
    {
        return $user->can('organization.branches.activate');
    }

    public function deactivate(User $user, Branch $branch): bool
    {
        return $user->can('organization.branches.deactivate');
    }

    public function manageAdministrators(User $user, Branch $branch): bool
    {
        return $user->can('organization.branch_administrators.assign')
            || $user->can('organization.branch_administrators.revoke');
    }

    public function manageSettings(User $user, Branch $branch): bool
    {
        return $user->can('organization.branches.manage');
    }
}
