<?php

namespace Modules\Organization\Support;

use App\Models\User;
use Modules\Organization\Models\BranchAdministrator;

/**
 * Centralized branch-level authorization scope.
 *
 * Returns null  → user has global (organization-wide) visibility.
 * Returns int[] → user is restricted to those branch IDs (own branch +
 *                 any branch they actively administrate).
 *
 * Views never decide this — controllers/policies call for() and apply it.
 */
class BranchScope
{
    /** Permissions that confer organization-wide visibility. */
    private const GLOBAL_PERMISSIONS = [
        'organization.branches.manage',
        'organization.company.view',
        'organization.branches.activate',
        'organization.branches.deactivate',
        'organization.branch_administrators.assign',
    ];

    public static function for(User $user): ?array
    {
        foreach (self::GLOBAL_PERMISSIONS as $perm) {
            if ($user->can($perm)) {
                return null; // global
            }
        }

        $ids = BranchAdministrator::active()->where('user_id', $user->id)
            ->pluck('branch_id')->all();
        if ($user->branch_id) {
            $ids[] = $user->branch_id;
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /** True when the user may see this branch (assumes view permission held). */
    public static function canSee(User $user, int $branchId): bool
    {
        $scope = self::for($user);

        return $scope === null || in_array($branchId, $scope, true);
    }

    /** Apply the scope to a query constrained on `branch_id` (or `id`). */
    public static function apply($query, User $user, string $column = 'branch_id')
    {
        $scope = self::for($user);
        if ($scope === null) {
            return $query;
        }

        return $query->whereIn($column, $scope === [] ? [-1] : $scope);
    }
}
