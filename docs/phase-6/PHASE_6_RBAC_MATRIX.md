# Phase 6 — RBAC

| Permission | group_finance_director | finance_manager | accountant | cashier | finance_read_only |
|---|---|---|---|---|---|
| finance.dashboard.view | ✓ | ✓ | ✓ | ✓ | ✓ |
| finance.accounts.* | ✓ | ✓ | view | – | view |
| finance.journals.create/submit | ✓ | ✓ | ✓ | – | – |
| finance.journals.approve/post | ✓ | ✓ | – | – | – |
| finance.journals.reverse | ✓ | ✓ | – | – | – |
| finance.payables/receivables.manage | ✓ | ✓ | ✓ | – | – |
| finance.payments.manage | ✓ | ✓ | – | ✓ | – |
| finance.banks/reconciliation.manage | ✓ | ✓ | – | ✓ | – |
| finance.budgets.manage | ✓ | ✓ | – | – | – |
| finance.periods.close | ✓ | ✓ | – | – | – |
| finance.periods.reopen | ✓ | – | – | – | – |
| finance.settings.manage | ✓ | – | – | – | – |
| finance.reports/ledger/audit.view | ✓ | ✓ | ✓ | – | ✓ |

27 permissions total. Enforced via `can:` middleware on web routes; services add self-approval and company-scope checks.
