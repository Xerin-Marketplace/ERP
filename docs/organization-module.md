# Organization Module — Technical Documentation

**Module:** `Modules/Organization` · **Phase:** 1 · **Engine:** nwidart/laravel-modules 13.x

The Organization module models the legal and operational structure of Xerin Group:
companies (holding, subsidiaries, associates, entities), branches, physical locations,
and departments — including their hierarchies, statuses, history and audit trail.

---

## 1. Data model

| Table | Purpose |
|---|---|
| `companies` | Legal entities. Self-referencing `parent_company_id` for holding → subsidiary trees. UUID, code (unique), type, contacts, addresses, currency, timezone, status. |
| `branches` | Operational units belonging to a company (`company_id` FK). Type: `head_office`, `regional_office`, `branch`, `warehouse`, `site`. `manager_id` is reserved for the future Employee module. |
| `locations` | Physical sites (warehouse, office, site, …) attached to a company and optionally a branch. A location is **not** a branch. |
| `department_definitions` | Reusable department classifications (IT, HR, FIN, …). Seeded, configurable, never hardcoded in logic. |
| `departments` | Organizational placements of a definition inside a company (+ optional branch + optional parent department). The same definition can exist in many branches without duplication. |

Key distinction: `department_definitions` = *what* a department is (catalog);
`departments` = *where* it exists in the organization.

Statuses everywhere use `Modules\Organization\Enums\EntityStatus`:
`active` → `suspended` → `archived`. No hard deletes — archival preserves history.

## 2. Hierarchy rules

- `OrganizationService::assertCompanyParentValid()` — a company cannot be its own
  parent and cannot be placed under one of its own descendants (cycle detection
  via iterative descendant walk).
- `assertDepartmentParentValid()` — same guarantees for `departments.parent_id`.
- `StoreDepartmentRequest` additionally validates: a branch-scoped department's
  branch must belong to the same company **and** be `active` (suspended/archived
  branches reject new assignments).
- All mutations run inside `DB::transaction`.

## 3. Service layer

`Modules\Organization\Services\OrganizationService` is the single write-path:
`createCompany / updateCompany / setCompanyStatus`, `createBranch / updateBranch /
setBranchStatus`, `createLocation / updateLocation / setLocationStatus`,
`createDepartment / updateDepartment / setDepartmentStatus`,
`overview()` (60s cached metrics) and `structureTree()` (nested hierarchy).

Every mutation:
1. writes the row,
2. records an audit row through shared `App\Services\AuditService`
   (`company.created`, `branch.suspended`, `department.transferred`, …),
3. dispatches a typed event (`Modules\Organization\Events\*`).

A queued listener (`Listeners\InvalidateOrganizationCache`, `ShouldQueue`)
clears `organization.overview` / `organization.structure` cache keys after any
change — dashboards never serve stale counts.

## 4. Authorization

26 granular permissions (seeded idempotently):

```
organization.view  organization.manage
organization.structure.view  organization.audit.view
company.{view,create,update,activate,suspend,archive}
branch.{view,create,update,activate,suspend,archive}
location.{view,create,update,delete}
department.{view,create,update,activate,suspend,archive}
```

- `super-admin` receives all of them; `admin`/`manager` receive read-only org
  permissions. Nothing is granted to plain users.
- Enforced via `permission:` middleware on every route plus an in-controller
  `can()` check for granular status actions (`activate`/`suspend`/`archive`).
- Web + API share the same permission names (single `web` guard by design).

## 5. Web screens (`/organization/…`)

| Route | Screen | Permission |
|---|---|---|
| `GET /organization` | Overview dashboard (real metrics + recent activity) | `organization.view` |
| `organization/companies*` | List / create / show / edit / status | `company.*` |
| `organization/branches*` | List / create / show / edit / status | `branch.*` |
| `organization/locations*` | List / create / status | `location.*` |
| `organization/departments*` | List / create / show / edit / status | `department.*` |
| `organization/structure` | Interactive tree (expand/collapse, search) | `organization.structure.view` |
| `organization/audit` | Audit history w/ filters | `organization.audit.view` |
| `organization/settings` | Org defaults via SettingsService | `organization.manage` |

All lists support search, filters, sorting and pagination. Status changes are
confirmation-dialog POSTs to `…/status`.

## 6. API (`/api/v1/organization/…`)

Standard envelope via `App\Support\ApiResponse`; Sanctum + `account.active` +
permission middleware. Endpoints documented in `docs/api/openapi.yaml`
(tag **Organization**) and browsable at `/api-docs`.

`POST /api/v1/organization/{entity}/{id}/status` — unified status endpoint where
`entity` ∈ `companies | branches | locations | departments`.

## 7. Tests

`Modules/Organization/tests/Feature/OrganizationModuleTest.php` — 36 tests /
125 assertions covering creation, hierarchies, circular-prevention, duplicates,
permissions (403 vs 200), suspended-branch restrictions, archival-no-delete,
API envelope/pagination/filters, structure/overview endpoints, settings,
audit, and a smoke pass over **every** page.

Run: `php artisan test Modules/Organization/tests`

## 8. Data isolation (forward design)

Ownership is validated server-side on every mutation (branch→company,
department→branch/company). Company/branch scoping for multi-tenant reads is
prepared by the `created_by`, `company_id`, `branch_id` columns — a global
scope / policy layer lands with the Employee module which introduces
per-branch user assignment.
