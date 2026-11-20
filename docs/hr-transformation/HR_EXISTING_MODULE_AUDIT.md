# HR-00 — Existing Module Audit

Date: 2026-02-15 · Baseline: 286/286 tests, Postgres production

## Category A — Existing & complete (re-verified)

| Spec module | Found | Location |
|---|---|---|
| Attendance | ✅ records, events, corrections, devices, import logs, shifts, clock in/out | `Modules/HR` (7 tables) |
| Leave | ✅ types, balances, requests, transactions, approvals | `leave_*`, `hr_approvals` |
| Overtime | ✅ requests + approval workflow | `overtime_requests` |
| Recruitment | ✅ requisitions, vacancies, applications, interviews | 4 tables |
| Performance | ✅ cycles, objectives, reviews | 3 tables |
| Training | ✅ programs, enrollments, certifications | 3 tables |
| Disciplinary | ✅ cases + actions, restricted | 2 tables |
| Benefits/Welfare | ✅ welfare records (health, insurance, pension) | `welfare_records` |
| HR documents | ✅ employee_documents + categories, expiry, permission-gated | Employee module |
| Onboarding | ✅ records + task checklists | Employee module |
| Offboarding | ✅ records, clearance, access revoke | Employee module |
| Employment records/contracts | ✅ `employee_employment_records` | Employee module |
| Assignments/transfers | ✅ `employee_assignments` + transfer workflow | Identity + Employee |
| HR dashboard | ✅ `HRDashboardService` + dashboard view | HR module |
| HR approvals | ✅ generic `hr_approvals` table | HR module |
| Self-service | ✅ `/hr/me` | HR module |

## Category B — Existing but incomplete

| Spec | Gap |
|---|---|
| Personal information | Missing: marital status, passport number, passport expiry, work permit, next of kin, postal address, region/city split |
| Employee profile tabs | 9 tabs exist (overview/personal/employment/assignment/documents/history/reporting/onboarding/activity) — spec wants ~19 incl. bank, compensation, payroll, benefits, attendance, leave, disciplinary |
| Benefits | `welfare_records` exists but no enrollment/eligibility/provider model |
| Contracts | `employee_employment_records` exists but no renewal-request workflow or expiry alerts |
| HR settings | Document categories + grades seeded, but no HR configuration center UI |
| Permissions | `hr.*` + `attendance/leave/shift/overtime/recruitment/performance/training/disciplinary/welfare` exist — **no `hr.bank.*`, `hr.compensation.*`, `hr.payroll.*`** |

## Category C — Missing entirely

| Spec module | Status |
|---|---|
| **Bank & Payment Details** | ❌ no table, model, UI — **build** |
| **Compensation Management** | ❌ no salary component/revision tables — **build** |
| **Payroll Management** | ❌ no periods/runs/items/payslips — **build** |
| Salary revision workflow | ❌ approvals for salary changes — **build** |
| Sensitive-data masking | ⚠️ national_id encrypted (Phase 3); bank data will follow same pattern |
| Global HR Control Center | ⚠️ partial — employee stats added to `/system-admin`; dedicated HR section needed |
| Country payroll configs | ❌ no per-country rule tables — **build with config, not invented rates** |

## Decision

Build the 3 missing pillars (Bank / Compensation / Payroll) + extend personal details + profile tabs + payroll permissions. Everything else is integration wiring.
