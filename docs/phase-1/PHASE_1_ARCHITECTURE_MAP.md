# Phase 1 — Architecture Map

```
Organization (source of truth: companies, branches, departments, locations,
            department_definitions, branch_settings, branch_administrators)
    │  consumed via models + OrganizationService
    ├── Identity        users.branch_id, registration branch/company/dept rules
    ├── Employee        job_positions (company_id), employees.placement (branch/dept),
    │                   employment records (assignment history — spec §6.2 contract)
    ├── HR              attendance records carry branch context
    ├── Ticketing       tickets carry branch of requester
    └── SystemAdministration  platform settings; organization.* perms enforced here
```

## Decisions

- **Positions** → reuse `job_positions` (Employee) — richer than spec minimum (grade, reports_to); Organization adds a management surface + API over the same table. Single source of truth, no duplication.
- **Branch admin** → `branch_administrators` join table (branch_id, user_id, assigned_by, revoked_at) — history retained, no user duplication.
- **Branch settings** → key/value table + typed service, consistent with `system_settings` pattern.
- **Permissions** → spec naming `organization.<entity>.<action>`; legacy `branch.*`/`company.*` names kept in DB as deprecated aliases for one release, routes use new names only.
- **Branch scope** → `BranchAccess` policy helpers: global (Group Admin perms) vs scoped (`users.branch_id` match) enforced in policies + query scopes, never in views.
- **Status model** → keep EntityStatus enum (active|suspended|archived); spec "deactivate" maps to `suspended` (soft, reversible) — recorded via `deactivated_at`; `archived` reserved for end-of-life.
