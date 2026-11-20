# KPI Dictionary — Executive Command Center

All figures aggregate real records only; nothing is fabricated. Every KPI
below states its formula and source. Currency rule: GL aggregates are in the
posting currency; branch comparison never sums across currencies.

## Financial KPIs

| KPI | Formula | Source |
|---|---|---|
| Revenue | Σ(credit−debit) on GL entries where account type=revenue, entry_date in period | `general_ledger_entries` ⋈ `chart_of_accounts` |
| Expenses | Σ(debit−credit) on type=expense, same window | same |
| Net Profit | Revenue − Expenses | derived |
| A/R Outstanding | Σ(total − received_amount) on posted/partially_paid customer invoices | `customer_invoices` |
| A/P Outstanding | Σ(total − paid_amount) on posted/partially_paid supplier invoices | `supplier_invoices` |
| Cash & Bank | Σ book balance of active bank accounts as of `to` | `FinanceDashboardService` → `BankAccountService` |
| AR/AP Ageing | bucketed open balances | `ReceivableService::aging` / `PayableService::aging` |
| Revenue trend | monthly Σ by account type, `SUBSTR(entry_date,1,7)` buckets | GL |

## Commercial KPIs

| KPI | Formula | Source |
|---|---|---|
| Customers | count of customers (branch-scoped optional) | `customers` |
| New Customers | customers created within period | `customers.created_at` |
| Gross Sales | Σ(total) on posted/paid customer invoices in period | `customer_invoices` |
| Avg Invoice | gross_sales / invoice count | derived |
| Top Customers | Σ(total) grouped by customer_id, desc, top 10 | `customer_invoices` |
| Sales by Month | Σ(total) per `SUBSTR(invoice_date,1,7)` | `customer_invoices` |

## People & Work KPIs

| KPI | Formula | Source |
|---|---|---|
| Active Employees | employees where status=active | `employees` |
| Open Tasks | tasks where status ∉ {completed, cancelled} | `work_tasks` |
| Overdue Tasks | open tasks where due_at < now | `work_tasks` |
| Completed (month) | completed_at in current month | `work_tasks` |
| Tasks by Status | count grouped by status | `work_tasks` |

## Tender KPIs

| KPI | Formula | Source |
|---|---|---|
| Active Tenders | stage ∉ {awarded, lost, cancelled} | `tender_opportunities` |
| Tenders Won | stage = awarded | `tender_opportunities` |
| Pipeline Value | Σ(estimated_value) on active stages | `tender_opportunities` |
| Awarded Value | Σ(submitted_value) where awarded | `tender_opportunities` |

## Governance

- **Comparison periods**: the previous window is the same-length period
  immediately before `from`; delta% = (cur−prev)/|prev|×100.
- **Filters**: `range` presets + `from/to` + `branch` apply to every widget
  server-side — no client-side hiding.
- **Authorization**: `executive.command.view` on every route (web + API).
- **Activity feed**: append-only `audit_logs`; not editable via the app.
- **Permissions to extend**: payroll/HR-sensitive fields stay behind HR perms
  — the executive pages never expose them.

## API surface

`GET /api/v1/executive/{dashboard|financial|branches|departments|sales|employees|tasks|tenders|activity}?range=&from=&to=&branch=&department=`
