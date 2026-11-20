# ADMIN SECURITY MODEL

## Layer order (every request)
auth → account.active → permission middleware → RoleAssignmentGuard
(tier) → AdminDelegationService (branch + delegation scope) → action.

## Protected surfaces
- `super-admin` in `PROTECTED_ROLES`; only super-admin assigns it;
  last-super-admin demotion blocked.
- `GLOBAL_ROLES = [super-admin, branch-admin]` can never appear in a
  delegation list (stripped server-side AND blocked at assert time).
- Self-escalation blocked twice: tier check + same-id role change block.
- Branch admin console routes `assertGlobal()` → super-admin only.
- No default passwords created; `super-admin` is never auto-assigned
  to a guessed account (existing admin@xerinhub.com seeding is the only
  historical exception, preserved for the bootstrap account).

## Audit
admin.branch_admin.{assigned,suspended,reactivated,revoked} +
users.role_changed → `audit_logs` (actor, resource, old/new, IP, UA).
