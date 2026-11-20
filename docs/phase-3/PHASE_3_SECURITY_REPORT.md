# Phase 3 Security Report

## Controls added

| Control | Implementation | Test evidence |
|---|---|---|
| Backend-only employee numbering | `employee_number` stripped from validated input in `EmployeeController::store`; generation inside `EmployeeNumberService` transaction (`lockForUpdate`) — race-safe, cannot be forged or duplicated by clients | `test_employee_number_is_generated_server_side_not_from_input` |
| Sensitive-data encryption at rest | `employee_personal_details.national_id` → `encrypted` cast + `text` column; ciphertext in DB, decryption only through the model | `test_national_id_is_encrypted_at_rest` |
| Auditable lifecycle | Every status change needs a reason + authorized actor; writes `audit_logs` + `employee_events`. Profile edit can no longer change status (removed from update validation) | `test_status_transition_is_audited_with_reason_and_actor` |
| Per-transition permissions | `employee.suspend`, `employee.terminate`, `employee.approve` seeded and enforced in `LifecycleController@transition` | `test_suspend_requires_suspend_permission` |
| Branch-scoped numbering | Per-branch sequences (`EMP-TZ-*`, `EMP-DXB-*`); placement FKs validated by `AssignmentService` | `test_employee_numbers_are_scoped_per_branch` |

## Inherited controls (re-verified)

- **Branch/department scope** — `scopeFor`/`directory` + `AssignmentService::validatePlacement` (rejects cross-branch department) — the per-branch numbering test itself proves the rejection fires.
- **Document privacy** — downloads only through the permission-checked route; confidential categories flagged; no public URL.
- **Account linkage** — `employees.user_id` unique FK prevents duplicate auth accounts; transfer re-syncs the user's placement; offboarding revokes access; `RoleAssignmentGuard` still blocks privilege escalation.
- **Soft delete** — employees with history are never physically removed; `separated` is the terminal state.

## Residual risks / notes

- `employee_number` format encodes the branch code — an accepted trade-off per spec.
- `national_id` encryption protects at rest; reads are gated by `employee.profile.view` — callers must not leak it into unpermissioned views (form/show already restrict to `employee.update`/`profile.view` holders).
- The transition endpoint returns 422 `abort` messages rather than validation errors for same-status attempts — acceptable UX for an admin dropdown that disables the current status.
