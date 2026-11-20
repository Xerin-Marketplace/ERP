# Phase 2 — RBAC Matrix

## Canonical permission groups (spec §6)
- Organization: organization.dashboard.view, organization.branches.view/manage (+create/update/activate/deactivate from Phase 1), organization.departments.view/manage (+create/update/deactivate), organization.positions.* , organization.branch_administrators.*
- Users: users.view/create/update/activate/suspend/deactivate/invite/reset_password/sessions.revoke/assignments.view/assignments.manage/assign_roles/assign_permissions
- Roles & permissions: roles.view/create/update/delete/assign · permissions.view/manage · access.audit.view
- HR (existing): hr.dashboard.view, hr.employee.view/manage, attendance.*, leave.*, etc.
- IT (existing Ticketing): ticket.*, task.*, sla.manage, escalation.manage, knowledge_base.manage — spec `it.tickets.*` maps onto these; it.dashboard.view + it.availability.manage seeded for future
- Reception (future): reception.dashboard.view, reception.visitors.create/view, reception.requests.manage — seeded now, unenforced until the module exists
- Legacy `user.*`/`role.*` names remain granted (deprecated aliases).

## Default grants (highlights)
| Role | Key grants |
|---|---|
| super-admin | everything |
| group-administrator | all organization.* + users.view/invite/reset_password/assign_roles + roles.view + permissions.view + access.audit.view |
| branch-manager | users.view, users.assignments.view + scoped org view |
| it-administrator | all users.*, roles.*, permissions.* (module admin), ticket.* |
| receptionist | users.view + reception.* |
| department-manager, auditor, management-viewer | read-oriented sets |

## Escalation protection
- Privileged-role grant requires equal/higher tier (guard + tests).
- Matrix save rejects permissions the actor doesn't hold (non-super-admin).
- Self role-change blocked for non-super-admin.
