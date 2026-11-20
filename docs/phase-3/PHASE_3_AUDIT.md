# Phase 3 Audit — Employee Management

Date: 2026-02-15

## Baseline (pre-change)

The repository already contained a substantial `Modules/Employee` module (~80% of the Phase 3 spec):

| Spec requirement | Found in repo |
|---|---|
| Employee profiles | `employees` + `employee_personal_details` (name, preferred name, gender, DOB, nationality, photo ref, contact fields, national_id) |
| Contact info | `phone`, `alt_phone`, `personal_email`, `residential_address`, `official_email`, `work_location` |
| Emergency contacts | `employee_emergency_contacts` (name, relationship, phone, alt) |
| Employee numbering | `EmployeeNumberService` — race-safe (`lockForUpdate`) but **global `XERIN-EMP-YYYY-NNNNNN`, not branch-scoped** |
| Assignments | Current placement on `employees` (company/branch/department/job_position/manager, added 200003); history via `employee_assignments` (Identity) synced by `TransferService` |
| Positions/grades | `job_positions`, `job_grades` + `StructureController` CRUD |
| Reporting lines | `employee_reporting_relationships` + circular-check in `ReportingService` |
| Transfers | `employee_transfer_requests` + request/approve/reject workflow |
| Lifecycle | statuses `onboarding\|active\|suspended\|separated`; onboarding + offboarding workflows with task checklists |
| Status history | `employee_events` (type/description/metadata/actor) |
| Documents | `employee_documents` + `employee_document_categories`, permission-gated download via controller (not public URL) |
| Contracts | `employee_employment_records` (contract type, dates, confirmation status, renewal chain) — covers spec's `employee_contracts` |
| Dashboard | `EmployeeService::dashboard` — totals, active, probation, expiring contracts, pending transfers/offboarding, per-company/branch |
| Self-service | `SelfServiceController` (`/employee/me`) |
| Account linkage | `employees.user_id` unique; Identity `ProvisioningService`/`InvitationService`; transfer approval re-syncs IAM placement |
| Permissions | `employee.*` family (23 permissions) granted to 10 roles |
| Exports | `GET /employee/export` CSV |

## Gaps implemented in Phase 3

1. **Branch-scoped numbering** — spec requires `EMP-TZ-000001`/`EMP-DXB-000001`. `EmployeeNumberService::next(Branch)` now generates `EMP-{branch_code}-{seq}` per branch; `nextGlobal()` kept for unplaced employees. `EmployeeService::create` passes the validated placement's branch.
2. **Lifecycle statuses** — spec's set (draft, pending_approval, active, on_probation, on_leave, suspended, resigned, terminated) added as `Employee::STATUS_*` constants + `STATUSES` list; legacy `onboarding`/`separated` retained for existing data.
3. **Auditable transitions** — `EmployeeService::transitionStatus(employee, to, actor, reason, effectiveAt)` writes `audit_logs` (`employee.status_changed` with old/new/reason/effective_at/actor) + an `employee_events` timeline entry. Exposed at `POST /employee/{employee}/status` (`LifecycleController@transition`) with per-target permission (`employee.suspend`/`employee.terminate`/`employee.approve`/`employee.update`) and a status dropdown + reason form on the profile page.
4. **Backend-only numbering** — `employee_number` removed from the store request's validated input (`unset` guard) so the frontend cannot inject numbers.
5. **Sensitive-data encryption** — `employee_personal_details.national_id` widened to `text` and given the `encrypted` cast; ciphertext at rest, plaintext on authorized reads. Migration `2026_12_01_000001`.
6. **Lifecycle permissions** — `employee.suspend`, `employee.terminate`, `employee.approve` added to `EmployeeDatabaseSeeder::PERMISSIONS`; granted to group-administrator (all), company-administrator (suspend), super-admin (all via the seeder's wildcard grant).
7. **Status via profile-edit removed** — `update()` no longer accepts `status`, so transitions cannot bypass reason/audit.

## Deliberately not changed

- **Module name** — `Modules/Employee` already satisfies the spec's `EmployeeManagement`; creating a second module would duplicate every table. Documented as the canonical mapping.
- **Permission prefix** — `employee.*` (repo canonical) instead of spec's `employees.*`. It is referenced by 8 controllers, 6 services, 8 views and all Employee routes; renaming would be cosmetic churn (same precedent as Phase 2's `ticket.*`). Spec mapping documented in the architecture doc.
- **No `employee_assignments` duplication** — the table already lives in Identity and is maintained by `AssignmentService` on every placement change/transfer.
- **No separate contracts table** — `employee_employment_records` already models contracts (type, start/end, confirmation status, renewal parent).

## Baseline verification

Phase 1 + Phase 2 verified complete (268/268 and 281/281 tests in prior phases). Employee module: 28 tests, 1 failure pre-existing → the numbering test, which asserted the *old* global format and was updated as part of the spec change.
