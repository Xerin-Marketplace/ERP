# Super Admin — HR Oversight Guide

Super Admin holds every `hr.*`/`employee.*` permission (seeded) → full read + manage across all branches.

Key surfaces:
- `/system-admin` — platform + employee stats cards
- `/employee` — global directory (view_all), create/edit, status transitions
- `/hr/dashboard` — workforce metrics
- `/hr/compensation` — approve/reject salary revisions (cannot approve own)
- `/hr/payroll` — periods, process, approve, mark paid
- Employee profile → Bank / Compensation tabs — verify bank accounts, propose revisions
- `/identity/*` — user/role/permission administration

Safeguards: can't approve own salary revision; payroll processor≠approver (super-admin exempt); all actions in audit_logs.
