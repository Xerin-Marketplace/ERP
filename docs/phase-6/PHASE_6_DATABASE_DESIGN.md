# Phase 6 — Database Design

33 tables across 5 migrations (see `Modules/Finance/database/migrations`).

| Group | Tables |
|---|---|
| Core | financial_settings, financial_years, financial_periods, exchange_rates, account_mappings |
| COA/GL | chart_of_accounts, journals, journal_lines, general_ledger_entries, finance_approvals |
| AP/AR | suppliers, supplier_invoices(+lines), customers, customer_invoices(+lines), payment_records, payment_allocations |
| Expenses | expense_categories, expense_claims |
| Bank | bank_accounts, bank_transactions, bank_reconciliations |
| Budget | budgets, budget_lines |
| Tax | tax_categories, tax_rates, tax_transactions |
| Assets | asset_categories, fixed_assets, asset_depreciation_schedules |

## Key decisions
- `general_ledger_entries` is **append-only**: no update/delete anywhere in code; `journal_line_id` UNIQUE.
- Money columns `DECIMAL(18,2)`; rates `DECIMAL(18,8)`/`DECIMAL(8,4)`. No floats.
- `tax_transactions.rate_applied` snapshots the applied rate (historical reproducibility).
- Documents keep `journal_id` back-reference for drill-down; journals keep polymorphic `source_type`/`source_id`.
- No cascading deletes on posted history: GL FKs are restrict-style (no `cascadeOnDelete` on entries).
- Company scope column on every transactional table; indexes on (company_id,status), (account_id,entry_date).
