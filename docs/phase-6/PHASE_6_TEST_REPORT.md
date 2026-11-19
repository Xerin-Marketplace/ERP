# Phase 6 — Test Report

`Modules/Finance/tests/Feature/FinanceModuleTest.php` — **30 tests, 68 assertions, all passing**.

Covered: balanced/unbalanced posting, inactive + non-postable account rejection, double-post prevention, immutability, reversal integrity (net GL = 0), locked period, self-approval block, trial balance zero-net, running balances, GL-only-from-posted, AP/AR posting entries, duplicate supplier invoice (unique key), partial + full payment allocation, overpayment rejection, expense approval gate, bank reconciliation difference, budget block/warn policies, tax snapshot, single-post depreciation, dashboard real-data metrics, cross-company isolation, API lifecycle + unbalanced 422 rollback.

Full suite after integration: **241 tests / 627 assertions — all green** (Phases 0–5 intact).
