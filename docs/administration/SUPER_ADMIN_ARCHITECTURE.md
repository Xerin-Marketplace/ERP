# SUPER ADMIN ARCHITECTURE

- Role `super-admin` = global authority; never branch-scoped.
- `AdminDelegationService::isSuperAdmin()` short-circuits all scope checks.
- Console: `GET /identity/admin-console` (permission `admin.console.view`)
  — super-admin sees all branches/users/role distribution.
- Branch Admins page: `GET|POST /identity/admin-console/branch-admins[...]`
  — `assertGlobal()` hard 403 for non-super-admin (tested).
