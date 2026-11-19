# Employee Module — Technical Documentation

**Module:** `Modules/Employee` · **Phase:** 3 · **Depends on:** Core, Organization, Identity

Workforce administration: employee records, positions/grades, documents,
reporting lines, transfers, promotions/changes, onboarding, offboarding,
self-service, directory and dashboard.

## 1. Design decisions

- **Single source of truth per concern.** `employees` holds the *current*
  placement snapshot (company/branch/dept/position/manager). History lives in
  two append-only places: `employee_events` (HR timeline) and Identity's
  `employee_assignments` (IAM placement history for linked accounts) — kept in
  sync by `TransferService`/`ChangeService`. Nothing is overwritten.
- **Employee ≠ User.** `employees.user_id` is nullable+unique — an employee
  record can exist before ERP provisioning, and ERP access is revoked on
  separation without deleting the employee record.
- **Org data is referenced, never duplicated** — placement validation delegates
  to Identity's `AssignmentService::validatePlacement` (branch∈company,
  dept∈company, branch-scoped dept compat, all active).
- **Numbers:** `XERIN-EMP-YYYY-######` via `EmployeeNumberService`
  (`lockForUpdate` + max + unique index, inside the create transaction).

## 2. Tables (14)

`employees`, `employee_personal_details`, `employee_emergency_contacts`,
`employee_document_categories` (11 seeded, `is_confidential`+`requires_expiry`
flags), `employee_documents` (private storage via FileService, auto-versioned),
`employee_events` (append-only timeline), `job_grades` (9 seeded G1–G9),
`job_positions` (company+dept+grade+reports-to), `employee_employment_records`
(contract chain w/ `renewed_from_id`), `employee_reporting_relationships`
(direct|functional, effective-dated), `employee_transfer_requests`,
`employee_change_requests` (8 types incl. `salary_reference` for Phase 6),
`employee_onboarding_records` + `_tasks`, `employee_offboarding_records`.

## 3. Access model (22 permissions)

- `employee.view_all` — unrestricted directory.
- `employee.view` — scoped to the viewer's own company (derived from *their*
  employee record; viewers without a record see nothing).
- `employee.profile.update_own` — self-service: personal contact fields only.
  Company/branch/dept/position/role are never self-editable.
- Workflow gates: `transfer.request`/`approve`, `promotion.request`/`approve`,
  `onboarding.manage`, `offboarding.manage`, `document.view/upload/delete`,
  `position.manage`, `grade.manage`, `export`, `history.view`, `reports.view`.

`EmployeeService::scopeFor()`/`canView()` enforce this on every query — web
and API share the same scoping.

## 4. Workflows

- **Transfer:** request → pending → approve (updates placement + writes new
  IAM assignment, ending the old one) / reject (note required).
- **Change:** 8 types → approve applies the governed effect (position for
  promotion/demotion, type, confirmation date, contract renewal chain).
- **Onboarding:** 8 default tasks; auto-completes → employee → `active`,
  notification sent.
- **Offboarding:** initiate → approve → complete: employee → `separated`,
  contract terminated, **`UserLifecycleService::deactivate`** revokes tokens,
  sessions and invitations via Identity. Record + audit + event kept forever.
- **Reporting:** `assignManager` ends the current direct relationship and
  inserts a new one; `createsCycle` walks the chain — circular assignments
  are rejected.

## 5. Surfaces

- Web `/employee/*`: dashboard, directory (filters+export CSV), create/edit,
  tabbed profile (9 tabs), documents, positions/grades, transfers, changes,
  onboarding workspace, offboarding workspace, `/employee/me` self-service.
- API `/api/v1/*`: `employees` CRUD + `dashboard`, `documents`, `history`,
  `reporting`, `transfer`, `promotion`, `offboarding`, `employee-positions`,
  `employee-grades`, `employee-document-categories`.

## 6. Integration points

- Identity `AssignmentService` (placement validation + IAM history sync)
- Identity `UserLifecycleService` (offboarding access revocation)
- `FileService` (private documents), `AuditService` (every mutation),
  `SettingsService`-ready task lists, queued `EmployeeNotification`
  (mail+database).

## 7. Known limitations

- Export is CSV (Excel/PDF rendering deferred — endpoint is `employee.export` gated).
- `salary_reference`/`grade_change` approvals record history only; payroll
  effects land with Finance (Phase 6).
- Reporting visualization is chain/reports lists; a graphical org chart is
  deferred to the Analytics phase.
