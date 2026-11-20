# Database Audit

- ~150 tables across 48 migrations (37 module + 11 app-level).
- PostgreSQL production; SQLite test suite — Postgres strictness issues already found & fixed (FK ordering `d3eca7d`, seeder cols `839ad6f`).
- Sensitive at-rest encryption: `employee_personal_details.national_id`, `employee_bank_accounts.account_number/iban`.
- Unique business refs: `employees.employee_number`, `tickets.ticket_number`, `attendance_records(employee_id,work_date)`, `payroll_runs(employee_id,payroll_period_id)`, `ticket_team_members(team_id,employee_id)`.

## Ownership map (verified)
- Organization → companies, branches, departments, locations, branch_settings, job_positions
- Identity → users, roles/permissions (Spatie), employee_assignments, registrations, access_invitations
- Employee → employees, personal details, employment records, documents, transfers, on/offboarding
- HR → attendance_*, leave_*, shifts, overtime, recruitment, performance, training, disciplinary, welfare, employee_bank_accounts, employee_compensations, payroll_*
- Ticketing → tickets + full service-desk schema
- Finance → chart_of_accounts, journals, GL, customer/supplier invoices, banks, budgets, assets, tax
- Reception → none (read-only over HR+Ticketing) + `ticket_team_members.availability`
- SystemAdministration → system_*

## Issues / recommendations
- `branch_id` on employees added via later migration (placement) — consistent now; enforce NOT NULL for future branch-scoped ops tables.
- No cargo/shipment/package tables — Domain B greenfield.
- `customers`/`suppliers` live in Finance scope; promote to dedicated masters when CRM/procurement land to avoid finance-owned master data.
- Postgres `FOR UPDATE` + `MAX()` pattern fixed in number generators — keep row-lock pattern for future sequences.
