# Phase 2 — Architecture

```
Identity module (source of truth: users, roles, permissions, invitations, assignments)
 ├── UserManagementController  users CRUD-lite + lifecycle + role/placement
 ├── AccessControlController   invitations, permission matrix, access admin
 ├── RoleManagementController  role CRUD + clone + delete-guard
 ├── Services: UserLifecycleService, AssignmentService, InvitationService,
 │            ProvisioningService, RoleAssignmentGuard (new)
 └── Policies/scope: Organization\Support\BranchScope (Phase 1), dept scoping via
     DepartmentPolicy (branch_id chain)
```

## Role naming (spec → repo)
Super Admin→super-admin · Group Admin→group-administrator · Branch Admin→branch-manager ·
HR Manager→hr-administrator · IT Manager→it-administrator · IT Technician→it-officer ·
Receptionist→receptionist · Department Head→department-manager · Employee→employee.
(Role *names* are labels only — every check is a permission check.)

## Privilege tiers (RoleAssignmentGuard)
super-admin=100 > group-administrator=80 > company-administrator=70 > others=0.
Granting a privileged role requires holding that tier; protected roles need super-admin;
self-elevation always rejected. Role deletes: protected + in-use blocked (pre-existing).

## Data
No new tables — reuses users/roles/permissions/pivots/access_invitations/branch_administrators.
