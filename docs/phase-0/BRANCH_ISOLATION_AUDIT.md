# Branch Isolation Audit

## Verified (with tests)
- `EmployeeService::scopeFor()` — `employee.view_all` sees all; others scoped to own branch/company/self.
- Reception: `ReceptionService::scopeBranch()` — receptionist hard-scoped to employee's branch; super-admin may pass `?branch_id=`. Cross-branch ticket creation → 403 (tested).
- Attendance, payroll periods, ticket queries accept optional branch scoping.
- Branch-scoped numbering (EMP-TZ, IT-TZ) reinforces ownership.

## Gaps / threats
- Branch isolation is per-service convention, not a global policy layer — a new module that forgets `scopeFor` leaks data silently. Recommend a shared `BranchScope` trait/query-scope for all branch-scoped models (Phase 1 hardening).
- Users have ONE branch via employee record — no multi-branch-user model yet (needed for group-level staff).
- `requester_branch_id` nullable on tickets — cross-branch display rules rely on service checks; add policy tests for IDOR on show routes (partially covered).
- Dubai/TZ timezones: stored config exists on branches (currency/timezone columns); attendance uses stored timestamps — verify shift-aware timezone math when cargo ops (24h) begin.
