# BRANCH ADMIN ARCHITECTURE

- Role `branch-admin` + ACTIVE `branch_administrators` row = authority.
- Scope resolution: `delegationFor()` → active, not revoked, not expired.
- Branch admin dashboard: same console URL — `isGlobal=false` branch view.
- User Management index auto-filters to delegation branch.
- Can assign only `delegable_roles`, grant only `delegable_permissions`.
