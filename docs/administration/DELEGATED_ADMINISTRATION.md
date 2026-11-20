# DELEGATED ADMINISTRATION

Table `branch_administrators` (extended 2026-12-14):
`user_id, branch_id, assigned_by, delegable_roles json,
delegable_permissions json, status, expires_at, suspended_at,
suspended_by, revoked_at, revoked_by`

## Delegation lifecycle (super-admin only)
- `AdminDelegationService::assignBranchAdmin()` — create/refresh
- `suspend()` / `reactivate()` / `revoke()` — revoke also removes the role
- Every transition writes an `audit_logs` record via `AuditService`.

## Server-side enforcement
- `assertCanActOn(actor,target)` — same-branch + never super-admin target
- `assertCanAssignRole(actor,role,target)` — wraps RoleAssignmentGuard,
  blocks global roles, checks delegable_roles
- `assertCanGrantPermission(actor,perm,target)` — delegable_permissions
- `assertBranchScope(actor,branchId)` — branch-level operations
- `UserManagementController@index` filters users to delegation branch;
  `assignRole` uses the same asserts (IDOR-proof: branch resolved
  server-side, never from request input).
