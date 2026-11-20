# Phase 2 — Security Report

## Verified controls
- Privilege escalation: tier-guarded role assignment; only super-admin grants super-admin; group-admin cannot grant super-admin (test); matrix rejects granting permissions the actor lacks.
- Self-elevation: own-role change 422; guard rejects self-assignment above current tier (test).
- Protected roles: super-admin cannot be deleted (test) or edited in the matrix; in-use roles cannot be deleted (pre-existing).
- Invitations: sha256-hashed tokens, expiring, single-use, revocable; admin never sets a user password — activation link flow.
- Password reset: admin action issues a fresh activation link (no plaintext password anywhere).
- Branch scope: `BranchScope` enforced in policies+queries; cross-branch view denied (test, Phase 1 regression re-run).
- Suspended/deactivated users blocked by `account.active` middleware.
- Audit: every privileged mutation writes audit_logs with actor, old/new, IP, UA.
- Least privilege: no blanket grants — each role receives an explicit list; `givePermissionTo` (never replaces other modules' grants).
- Session revocation exists (`users.sessions.revoke`).

## Notes / residual risks
- Matrix save syncs the whole role — a race between two admins could clobber; versioning recommended in a later hardening pass.
- `it-administrator` intentionally holds the full users/roles set (module admin) — review in production RBAC tuning.
- Role activate/deactivate: Spatie roles have no status column — documented, not implemented (spec allows).
