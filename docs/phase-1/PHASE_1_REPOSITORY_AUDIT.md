# Phase 1 — Repository Audit

Baseline commit: `f753914` (main). Working tree had uncommitted SystemAdministration work — preserved untouched.

## Stack (verified)

- Laravel 12, PHP 8.x, nwidart/laravel-modules, Blade + Tailwind + Alpine (no Angular), Sanctum, Spatie Permission, SQLite dev / MySQL target, PHPUnit.

## Modules present

Core, Organization, Identity, Employee, HR, Ticketing, Finance, SystemAdministration — all enabled in `modules_statuses.json`.

## Organization module (pre-existing — heavily reused)

- Tables: `companies`, `branches` (core table + module extension), `locations`, `department_definitions`, `departments`.
- `branches` core migration predates module: `name, code(unique), city, is_active`; extension adds `company_id, branch_type, manager_id, contact_person, phone, email, country, region, district, physical_address, postal_address, lat/long, opening_date, description, status, created_by`; converts `is_active`→`status` (EntityStatus: active|suspended|archived).
- `users.branch_id` FK exists — basis for branch scoping.
- Service layer: `OrganizationService` — transactions, AuditService logging, domain events for every mutation. Reused, not rebuilt.
- Routes: `/organization/*` (web) + `/api/v1/organization/*` (Sanctum) with permission middleware, Form Requests, controllers per entity.
- Views: index/create/edit/show for companies, branches, departments, locations + overview, structure, audit, settings.
- Nav: `OrganizationServiceProvider::registerNavigation()` — 7 items under "Organization" group.
- Tests: `OrganizationModuleTest` (RefreshDatabase, seeds roles + org seeder).

## Employee module (integration contracts already present)

- `job_positions` — code(unique), title, description, company_id, department_id, job_grade_id, reports_to, status → **reused for spec "Positions"** (no duplicate `positions` table created).
- `employees`, `employee_employment_records`, `employee_reporting_relationships`, `employee_transfer_requests` — satisfy spec's future assignment-history contracts.

## RBAC

- Roles already seeded: super-admin, group-administrator, company-administrator, branch-manager, department-manager, it-administrator, it-officer, hr-*, finance_*, employee, staff… (Employee/HR seeders).
- `group-administrator` exists but had **no organization permissions** — fixed by Phase 1 seeder.
- Permission convention pre-Phase-1: `branch.view`, `company.create`… — used ONLY inside Organization module → safe to migrate to spec convention `organization.branches.*`.

## Gaps found (all addressed in Phase 1)

1. Branches lacked `currency`, `timezone`, `country_code`, `postal_code`, `activated_at`, `deactivated_at`, `updated_by`.
2. No `branch_settings` or `branch_administrators` tables.
3. `departments.code` had no unique-per-branch constraint.
4. No DXB/TZ default branches (only seeded `HQ`).
5. No explicit activate/deactivate endpoints (generic status toggle only).
6. No branch administrator assignment mechanism.
7. No positions surface in Organization (job_positions lived only in Employee UI).
8. No branch-scoped policy (backend query scoping absent).
9. Identity user-management accepted `exists:branches,id` without active check.
10. Spec naming for permissions not yet adopted.
