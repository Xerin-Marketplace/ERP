# Phase 6 — Architecture

`Modules/Finance` is a modular-monolith finance domain split into logical areas:

- **Core**: `financial_settings`, `financial_years`, `financial_periods`, `exchange_rates`, `account_mappings`.
- **Accounting engine**: `JournalService` — the only writer of `general_ledger_entries`. Every posting validates balance, account postability/activity/company scope, and open period inside one DB transaction with row locking.
- **Sub-ledgers**: AP (`PayableService`), AR (`ReceivableService`), Expenses, Payments, Bank — each produces journals through the same engine (`source_type`/`source_id` link back to the document).
- **Controls**: `FinApprovalService` (polymorphic steps, self-approval blocked), period lock/reopen, immutable posted journals, reversal-only correction.
- **Reporting**: `LedgerService` (GL, trial balance, income statement, balance sheet) and `FinanceDashboardService` — all derived from posted GL + documents; nothing is manually editable.

## Journal lifecycle
`draft → submitted → approved → posted → (reversed via counter-journal)`

System journals (invoices, payments, depreciation) carry `source_type` and may post directly — the source document's own approval workflow already authorized them. Manual journals follow the approval gate configured per company (`financial_settings.require_journal_approval`).

## Idempotency & integrity
- `general_ledger_entries.journal_line_id` UNIQUE → a line can never post twice.
- `unique(asset_id, period_id)` on depreciation schedules.
- `unique(supplier_id, supplier_invoice_no)` duplicate-invoice detection.
- All money movement inside `DB::transaction` + `lockForUpdate`.
