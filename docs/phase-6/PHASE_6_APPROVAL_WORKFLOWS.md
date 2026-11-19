# Phase 6 — Approval Workflows

## Journal approvals (inline fields)
draft → submit → approve (≠ creator) → post. Rejection returns to editable `rejected` state.

## Document approvals (polymorphic `finance_approvals`)
Ordered role steps; rejection skips remaining steps; self-approval always blocked (validated in `FinApprovalService::decide`).

| Document | Default chain |
|---|---|
| ExpenseClaim | finance_manager (+ finance_director if amount > category.approval_threshold) |
| Budget | finance_manager |
| BankReconciliation | approver ≠ preparer (separation of duties) |

Routing is chain-configured in services; company/branch/dept routing hooks exist via document scope fields.
