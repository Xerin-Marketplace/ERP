# Phase 1 — RBAC Matrix

## Roles granted (idempotent seeder)

| Role | Organization permissions |
|---|---|
| super-admin | all (legacy + canonical) |
| group-administrator | ALL organization.* — full branch/dept/position/admin/settings management |
| company-administrator | same as group-administrator |
| branch-manager | dashboard.view, branches.view (scoped), departments.view/create/update/deactivate (scoped), positions.view — **no branch management** |
| department-manager | dashboard/branches/departments/positions .view (scoped) |
| admin, manager | view-level org permissions |

## Branch scope (`BranchScope` helper)

- Global (no scope): user holds any of `branches.manage|activate|deactivate`, `company.view`, `branch_administrators.assign` → Group Admin level.
- Scoped: user's `branch_id` + active `branch_administrators` rows → view restricted to those branches; enforced in `BranchPolicy`/`DepartmentPolicy` + controller queries — never in views.

## Permission naming

Canonical: `organization.<entity>.<action>` (spec §5.3). Legacy `branch.*`, `company.*`, `department.*`, `location.*` rows remain granted (deprecated) for compatibility; no route references them.

## Key checks verified by tests

- Non-privileged user → 403 on branch create/deactivate.
- branch-manager → cannot create/activate/deactivate branches.
- Scoped user → `view()` false for other branches; true after administrator assignment.
