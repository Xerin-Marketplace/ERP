# PERMISSION MATRIX (admin domain)

| Permission | super-admin | branch-admin | sys-auditor |
|---|---|---|---|
| admin.console.view | ✓ | ✓ | — |
| admin.branch.manage | ✓ | — | — |
| admin.delegation.manage | ✓ | — | — |
| admin.settings.global | ✓ | — | — |
| admin.settings.branch | ✓ | ✓ | — |
| admin.audit.view | ✓ | ✓ | ✓ |
| users.assign_roles | ✓ | ✓(delegable only) | — |

Roles seeded by `IdentityDatabaseSeeder` (idempotent, firstOrCreate +
syncPermissions; re-seeding produces no duplicates — tested).
