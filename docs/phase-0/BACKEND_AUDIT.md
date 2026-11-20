# Backend Audit

## Architecture (verified)
- `nwidart/laravel-modules` modular monolith; each module has `app/{Http/Controllers,Models,Services,Providers}`, `database/{migrations,seeders}`, `routes/{web,api}.php`, `resources/views`, `tests`.
- `ModuleServiceProvider` subclasses; module registration via `module.json` + `modules_statuses.json`.
- Central services in `app/Services`: `AuditService`, `NavigationRegistry`, `FileService`-style `ManagedFile`.
- Inter-module pattern: services + events + audit; e.g. Reception reads HR/Ticketing data without owning tables.

## Patterns verified
- **Numbering**: branch-scoped refs — `EMP-TZ-000001`, `IT-TZ-2026-000001`, `XERIN-PAY-*` — row-lock concurrency-safe (fixed for Postgres in `0d5650a`).
- **Encryption**: `national_id`, bank account numbers + IBAN encrypted at rest, masked display.
- **Approvals**: per-module (HR compensation, payroll, leave, transfers, ticket approvals, finance_approvals) — no central engine yet.
- **Soft deletes, audit columns** consistent on core entities.

## Gaps
- No central workflow/approval engine (each module re-implements).
- API v1 covers employees/core only partially — new HR pillars lack API endpoints.
- No event bus for cross-module domain events (needed for cargo lifecycle notifications).
- Queue driver `database` — fine now, switch to Redis before notification-heavy cargo ops.
