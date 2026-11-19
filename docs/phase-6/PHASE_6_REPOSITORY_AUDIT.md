# Phase 6 — Repository Audit

## Reusable infrastructure found
- **Modular engine**: nwidart/laravel-modules; modules register via `module.json` + `ModuleServiceProvider` (nav via `NavigationRegistry`).
- **Auth**: Sanctum (`auth:sanctum`) + `account.active` middleware; Spatie roles/permissions seeded per module.
- **Org scope**: `Company`/`Branch`/`Department` in Organization module; user scope derived from `employee` relation, never from request payload.
- **Audit**: `App\Services\AuditService::log(event, model, new: [...])`.
- **Notifications**: queued Laravel notifications (mail+database).
- **Files**: `managed_files` private storage; `receipt_file_id` FK used for expense receipts.
- **Numbering**: race-safe `XERIN-<P>-YYYY-######` pattern (`lockForUpdate` + max suffix) — mirrored in `FinNumberService`.
- **API conventions**: `{success,data}` envelope, `/api/v1/<module>`, OpenAPI at `docs/api/openapi.yaml`.
- **HR approval pattern**: polymorphic approval steps with self-approval blocked — mirrored in `finance_approvals`/`FinApprovalService`.

## Modules Finance integrates with
Organization (company/branch/dept scoping), Identity (users/roles), Employee (expense claim ownership, asset custodian), HR (future payroll hand-off via verified overtime), Ticketing (optional billable references — not wired yet).

## Missing infrastructure (implemented as minimal extensions)
- Double-entry engine, GL append-only store, COA, financial periods → built in this phase.
- `account_mappings` table for control-account resolution (ar/ap/cash/tax/depreciation).
- Polymorphic `finance_approvals` (HR pattern could not be reused directly — separate table keeps module boundaries).

## Architectural conflicts / risks
- No floating-point money anywhere: DECIMAL(18,2); exchange rates DECIMAL(18,8).
- SQLite (tests) vs MySQL (prod): date filters normalized via `whereDate`; no DB-specific SQL in services.
- Posted-journal immutability enforced in service layer; no update routes exist for posted journals.

## Implementation plan
Core settings/periods → COA + journal engine → GL + statements → AP/AR → expenses/payments/bank → budget/tax → assets → dashboard → hardening/docs.
