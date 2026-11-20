# ROLE HIERARCHY

## Authority model
```
COMPANY
├── super-admin        GLOBAL — all branches, all modules, all settings
├── branch-admin       BRANCH — one assigned branch via branch_administrators
│   └── delegates (department-manager, department-admin, custom branch roles)
├── department-manager / department-admin   BRANCH+DEPT (existing roles extended)
└── system-auditor     READ-ONLY audit scope
```

## Hard rules (enforced in `AdminDelegationService`, never in UI)
1. `super-admin` / `branch-admin` can only be assigned by a super-admin.
2. A branch-admin may only assign roles listed in `delegable_roles`
   and grant permissions listed in `delegable_permissions`.
3. A branch-admin may only act on users whose effective branch equals
   their `branch_administrators.branch_id` — client-supplied branch IDs
   are never trusted.
4. Suspended / expired / revoked delegations grant ZERO authority.
5. `branch-admin` role alone ≠ authority — an active delegation row is required.
6. The last active super-admin cannot be demoted (`assertNotLastSuperAdmin`).

## Role → identifier mapping (prompt → implementation)
| Prompt            | Seeded role        |
|-------------------|--------------------|
| super_admin       | `super-admin`      |
| branch_admin      | `branch-admin`     |
| department_manager| `department-manager` |
| department_admin  | `department-admin` |
| system_auditor    | `system-auditor`   |
