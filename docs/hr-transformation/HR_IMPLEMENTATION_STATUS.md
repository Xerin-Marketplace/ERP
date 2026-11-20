# HR Implementation Status

## Done this session (HR-04/05/06 + HR-02 fields)

- **Bank & Payment Details** — table, encrypted account/IBAN, masking, verification workflow, tab on employee profile, `BankDetailsService`, controller + routes, permissions.
- **Compensation** — component columns (basic + 6 allowances + OT rate + 4 deductions), immutable revisions (pending→approved→superseded), self-approval blocked, audit + employee_events timeline, profile tab, `/hr/compensation` admin page.
- **Payroll** — periods (per-branch optional), `process` generates draft runs from approved compensations (idempotent — no dupes), period approve (processor≠approver), mark-paid, reversal path, `/hr/payroll` page, XERIN-PAY numbering.
- **Personal info** — passport (encrypted-capable text), work permit, marital status, next of kin, country/region/city/postal.
- **Nav** — Compensation + Payroll items under Human Resources group.
- **Roles** — payroll-officer, payroll-approver.
- **Control center** — employee stats card on `/system-admin`.

## Pre-existing (audited, unchanged)
Attendance, leave, overtime, shifts, recruitment, performance, training, disciplinary, welfare, documents, onboarding, offboarding, transfers, HR dashboard, self-service, approvals engine.

## Not yet (roadmap)
- Payslip PDF rendering (data exists in `breakdown` — needs a print view).
- Per-country payroll rule tables (TZ/AE tax configs) — deliberately not invented; configure via compensation deduction columns until rules engine lands.
- Contract renewal-request workflow + expiry alerts (records exist; alerting pending).
- Benefits enrollment model (welfare_records covers basics).
- Employee profile: remaining spec tabs (attendance/leave/disciplinary/benefits/performance/training) — relations exist, tabs are view-layer work.
- HR configuration center UI (settings tables seeded; admin UI pending).
- API v1 resources for the new pillars.
