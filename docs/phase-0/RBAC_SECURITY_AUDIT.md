# RBAC & Security Audit

## Verified
- Spatie `web` guard; 253 permissions; `can:` middleware on all module routes + `account.active`.
- Roles: super-admin (all perms, protected), it/group/company-administrator, branch/department-manager, employee, hr-administrator/officer, receptionist, auditor, management-viewer + HR's payroll-officer/payroll-approver.
- Receptionist correctly EXCLUDES salary/bank/payroll/attendance-edit perms (tested).
- Sensitive encryption + masking verified (national_id, bank accounts).
- `account_security_events` logging; account suspension; registration review gate.
- Audit log on ticket creation, lifecycle transitions, compensation/payroll changes.
- Self-approval blocked on compensation revisions and payroll (requester≠approver).

## Gaps
- No MFA.
- Sensitive-record VIEW auditing is partial (changes audited; views not consistently).
- `employee.view_all` is the single global-scope grant — fine, but future branch-admin roles will need explicit scope objects.
- Field-level security: implemented ad-hoc (masked fields), no policy layer.
- Permission names mostly `module.action` — consistent enough; HR uses both `hr.*` and legacy `attendance.*`/`leave.*` — acceptable but document convention.
