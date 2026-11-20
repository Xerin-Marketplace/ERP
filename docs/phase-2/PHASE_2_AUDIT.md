# Phase 2 — Audit

## Pre-existing (reused, not rebuilt)
- Auth: session + Sanctum + `account.active` middleware; `users.status` (pending/active/suspended/deactivated) + `UserLifecycleService` transitions.
- Spatie Permission v6: roles/permissions/pivots all standard.
- Identity module: users index/show/update, assignRole (protected-role + self-change guards), suspend/activate/deactivate, revokeSessions, resendInvitation, reassign (validated placement, branch active check); roles index/create/edit/update/clone/destroy (protected + in-use safe); registration → provisioning → `AccessInvitation` (sha256-hashed tokens, `AccountActivationInvitation` queued notification).
- Phase 1: `BranchScope` + policies, `branch_administrators`, canonical `organization.*` perms.
- Roles already seeded incl. group-administrator, branch-manager, it-administrator, it-officer, hr-administrator, hr-officer, department-manager, employee, auditor.

## Gaps closed in Phase 2
1. No admin-initiated user creation — added invite flow (pending user + activation link, no admin-set password).
2. No pending-invitations admin view or revocation.
3. No permission matrix / permission-group browser.
4. No central access-administration view (branch/dept access + access history).
5. Privilege-escalation: `role.assign` holder could grant any non-protected role incl. group-administrator — now tier-guarded via `RoleAssignmentGuard`.
6. No admin password-reset path — added reset via fresh activation link.
7. Permission naming `user.*`/`role.*` → canonical `users.*`/`roles.*` (legacy kept granted).
8. `IdentityDatabaseSeeder::syncPermissions` would strip cross-module grants on reseed — fixed to `givePermissionTo` (same class of bug as the Finance fix).
9. Spec roles `receptionist`, `hr-administrator`, `hr-officer` not created by this seeder — added with scoped grants.
