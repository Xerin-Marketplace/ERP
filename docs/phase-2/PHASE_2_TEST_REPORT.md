# Phase 2 — Test Report

## New: tests/Feature/AccessControlPhase2Test.php — 13 tests, 47 assertions, all pass
- Spec roles + canonical permissions seeded; Identity seeder doesn't strip org grants (regression guard for the syncPermissions fix).
- Invite flow: user created pending + role + access_invitations row.
- Suspend/activate roundtrip; role create/clone; protected-role delete 403.
- Escalation: group-admin→super-admin grant blocked; self-elevation blocked; same-tier grant allowed.
- Matrix sync persists + audits; non-super-admin overreach 403.
- Branch scoping regression; access/matrix pages permission-gated.

## Full suite after Phase 2: 281 tests — 280 → 281/281 pass
(One stale grant `role.assign`→`roles.assign` updated in pre-existing IdentityModuleTest.)

## Manual smoke (dev server, super-admin): /identity/users, /users/create, /roles, /invitations, /permissions, /permissions/matrix, /access, /registrations → 200.
