# Module Dependency Map (actual, from code)

```
Organization (companies/branches/departments/positions)
 ├── Identity        — branch scoping, assignments reference org
 ├── Employee        — employees.branch_id/department_id/job_position_id
 ├── HR              — attendance/payroll/recruitment scoped to branch+dept
 ├── Ticketing       — requester_branch_id, teams.branch_id
 ├── Finance         — payroll periods branch-optional
 ├── Reception       — branch-scoped reads of HR+Ticketing
 └── SystemAdministration — global config

Identity (users/roles/permissions/assignments)
 ├── Employee        — employee.user_id linkage, invitations
 ├── HR              — payroll approvers, requester≠approver rules
 ├── Ticketing       — requester_id, assigned_agent_id, comments.author_id
 └── all modules     — `can:` permission middleware

Employee (employees master)
 ├── HR              — all HR records FK employee_id
 ├── Ticketing       — ticket_team_members.employee_id, on-behalf tickets
 └── Reception       — directory, ticket requester

HR (attendance/compensation/payroll — sources of truth)
 └── Reception       — read-only attendance monitor
 └── Finance         — payroll_runs → (pending GL posting link)

Ticketing (service desk source of truth)
 └── Reception       — creates tickets via TicketService::create (on-behalf)

Core (audit, navigation, managed_files)
 └── everything      — AuditService::log, NavItem registration, file storage
```

**No circular dependencies found.** Communication is service-call based, not raw cross-table writes.
**Domain B will need:** Customer (new master) → Cargo Ops → Shipment → Customs → Distribution → Delivery; Finance hooks at quotation-payment-invoice points; Notification events at each lifecycle milestone.
