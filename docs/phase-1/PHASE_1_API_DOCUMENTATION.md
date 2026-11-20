# Phase 1 — API (v1, Sanctum)

Base: `/api/v1/organization` — auth:sanctum + `account.active` + `permission:` middleware.

| Method | Endpoint | Permission | Notes |
|---|---|---|---|
| GET | /overview | organization.dashboard.view | real counts |
| GET/POST | /companies | organization.company.view / .create | search,status,paginate |
| GET/PUT | /companies/{id} | .view / .update | |
| GET/POST | /branches | organization.branches.view / .create | search,country,status |
| GET/PUT | /branches/{id} | .view / .update | BranchResource |
| POST | /branches/{id}/activate | organization.branches.activate | sets activated_at |
| POST | /branches/{id}/deactivate | organization.branches.deactivate | sets deactivated_at, soft |
| GET | /branches/{id}/departments | organization.branches.view | DepartmentResource |
| GET/POST | /branches/{id}/administrators | branch_administrators.view / .assign | |
| DELETE | /branches/{id}/administrators/{admin} | .revoke | soft-revoke |
| GET/PUT | /branches/{id}/settings | branches.view / branches.manage | key/value JSON |
| GET/POST | /departments, /departments/{id} (PUT) | organization.departments.* | code unique per branch |
| GET/POST/PUT | /positions[/{id}] | organization.positions.* | job_positions table |
| GET | /locations, /structure, /audit-logs | organization.locations.*/structure/audit | |

Responses use `ApiResponse::success(data, message, code)`; validation 422, authz 403, auth 401. All mutations run in transactions via `OrganizationService` and write `audit_logs`.
