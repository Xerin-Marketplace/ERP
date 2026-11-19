# Phase 5 — Human Resource Management

Enterprise HRM platform (`Modules/HR`): attendance, leave, shifts, overtime, recruitment, performance, training, disciplinary, welfare and HR documents.

## Capabilities

| Area | Implementation |
|---|---|
| Attendance | `attendance_records` — `XERIN-ATT-YYYY-######`, unique(employee, work_date); clock in/out, breaks, late/early/OT detection vs assigned shift + grace; `attendance_events` immutable punch log; `attendance_corrections` w/ approval; `attendance_devices` + `attendance_import_logs` for device adapters |
| Shifts | `shift_definitions` (day/night/flex/custom, working_days JSON, overnight auto-detect), `shift_assignments` — overlapping assignments auto-ended |
| Leave | `leave_types` (entitlement, eligibility JSON, approval_chain JSON, carry-forward, documents), `leave_balances` per year w/ entitled/carried/used/pending, `leave_transactions` ledger, `leave_requests` (Mon–Fri working-day counting, overlap guard) |
| Approvals | Generic `hr_approvals` polymorphic ordered steps — shared by leave, overtime, requisitions; self-approval blocked |
| Overtime | `overtime_requests` `XERIN-OVT-…`, 2-step approval (manager→HR), verified actual_hours feed future payroll |
| Recruitment | `recruitment_requisitions`→approval→`job_vacancies`→`recruitment_applications` (8 stages)→`interview_records` (score/outcome) |
| Performance | `performance_cycles`, `performance_objectives` (weighted), `performance_reviews` `XERIN-PRF-…` (self/manager/peer, unique per cycle+kind, 1–5 rating) |
| Training | `training_programs` (capacity, budget), `training_enrollments`, `training_certifications` (auto-issued for certifying programs, expiry tracking) |
| Disciplinary | `disciplinary_cases` `XERIN-CAS-…` — confidential, `disciplinary.view/manage` gated; `disciplinary_actions` append-only (warnings, suspension, appeal, notes) |
| Welfare | `welfare_records` `XERIN-WEL-…` — benefits/assistance/programs/activities |
| HR Documents | `hr_documents` `XERIN-DOC-…` — categories, versioning via supersedes, expiry, confidential flag (HR-manage only) |
| Announcements | `hr_announcements` with publish/expiry window |

## Security model

- `hr.dashboard.view` → org-wide HR metrics; officers scoped to own company.
- Self-service (`/hr/me`, `attendance.clock_in/out`, `leave.request`, `overtime.request`) requires linked `Employee` record.
- No self-approval: `LeaveService::decide` and `OvertimeService::decide` reject `employee->user_id === decider->id`.
- Disciplinary data requires `disciplinary.view` (index) / `disciplinary.manage` (mutations) — every action audited.
- `attendance_corrections` and `hr_documents.confidential` restricted to HR.
- Server-side timestamps on all punches; date-range queries use `whereDate` (portable across SQLite/MySQL).

## Permissions (33)

Full list in `HRDatabaseSeeder::PERMISSIONS`. Roles: `hr-administrator`, `hr-officer`, `manager` (approvals), `employee` (self-service), admin roles get full sets.

## Seed data

- 8 leave types (AL 28d, SL 126d, ML 84d female-only, PL 3d male-only, CL, STL, UPL unpaid, SPL) — Tanzanian-aligned but **configurable, not hardcoded**
- 4 shifts: DAY 08–17, NIGHT 18–06 overnight, FLEX, SAT

## Numbers

`HRNumberService` — `XERIN-ATT/ACR/LEV/OVT/REQ/VAC/APP/PRF/CAS/WEL/DOC-YYYY-######` row-lock + unique.

## Routes

Web: `/hr` — `me` (self-service), `dashboard`, `attendance`, `leave` (+`/approvals`), `shifts`, `overtime`, `recruitment`, `performance`, `training`, `disciplinary`, `welfare`, `reports`.
API: `/api/v1/hr/{dashboard,attendance,attendance/clock-in,attendance/clock-out,leave,leave/request,leave/balances,leave/types,leave/{id}/approve,leave/{id}/reject,shifts,overtime,overtime/request,recruitment/vacancies,performance}`.
