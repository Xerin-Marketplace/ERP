# Phase 3 Architecture — Employee Management

## Module

`Modules/Employee` (existing module, extended). The spec's `EmployeeManagement` name maps to this module — no second module was created.

## Layers

```
Http/Controllers   EmployeeController, StructureController, TransferController,
                   ChangeController, LifecycleController, DocumentController,
                   SelfServiceController, Api/V1/EmployeeApiController
Services           EmployeeService, EmployeeNumberService, AssignmentService(Identity),
                   TransferService, ReportingService, OnboardingService,
                   OffboardingService, DocumentService, ChangeService
Models             Employee, EmployeePersonalDetail, EmploymentRecord,
                   EmergencyContact, EmployeeDocument, DocumentCategory,
                   EmployeeEvent, JobPosition, JobGrade, ReportingRelationship,
                   TransferRequest, ChangeRequest, OnboardingRecord/Task,
                   OffboardingRecord
```

## Employee number generation

`EmployeeNumberService::next(?Branch)` → `EMP-{BRANCH_CODE}-{6-digit seq}` inside the caller's transaction with `lockForUpdate` — race-safe, one sequence per branch. `nextGlobal()` retains `XERIN-EMP-YYYY-NNNNNN` for employees with no branch. Never accepted from request input (stripped in `EmployeeController::store`).

## Lifecycle

`Employee::STATUSES`: draft, pending_approval, onboarding(legacy), active, on_probation, on_leave, suspended, resigned, terminated, separated(legacy).

Transitions only via `EmployeeService::transitionStatus` → `POST /employee/{e}/status`. Each transition: required reason, authorized actor, effective date → `audit_logs` row + `employee_events` timeline entry. Profile edits cannot change status.

Permission per target status:
- `suspended`, `on_leave` → `employee.suspend`
- `resigned`, `terminated`, `separated` → `employee.terminate`
- `pending_approval` → `employee.approve`
- `active`, `on_probation` → `employee.update`

## Placement & scope

Current placement on `employees`; history in `employee_assignments` (Identity `AssignmentService` — validates branch/department belong to company, writes history rows, syncs linked `users` placement). `EmployeeService::scopeFor`/`directory` restrict listing to the viewer's permitted company/branch/department.

## Sensitive data

`employee_personal_details.national_id` — `encrypted` cast (AES via app key); column widened to `text`. Read access gated by `employee.profile.view`. Documents stored via `DocumentService` on the configured disk; download only through the permission-checked controller route — no public URLs.

## Permission mapping (spec → repo)

`employees.*` spec names map 1:1 to the repo's canonical `employee.*` prefix (see audit doc — rename rejected as churn). `employees.suspend/terminate/approve` were genuinely new → added as `employee.suspend`, `employee.terminate`, `employee.approve`.

## Account integration

`employees.user_id` unique-FK to `users`. Linkage via Identity `ProvisioningService`; invitations via `InvitationService` (hashed token, emailed activation). Transfers re-sync the linked user's placement (and thereby branch-scoped permissions). Offboarding completion revokes ERP access. No privileged role is auto-granted — `RoleAssignmentGuard` still governs role assignment.
