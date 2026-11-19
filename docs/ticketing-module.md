# Phase 4 — Ticketing & Work Management

Enterprise service desk and work management platform (`Modules/Ticketing`).

## Capabilities

| Area | Implementation |
|---|---|
| Tickets | `tickets` — `XERIN-TKT-YYYY-######` (row-lock sequence + unique index), requester/assigned org context, priority/severity/impact/urgency, custom fields, asset reference |
| Lifecycle | `Ticket::TRANSITIONS` state machine: new→submitted→under_review→assigned→in_progress→pending→resolved→closed (+reopened). `ticket_status_histories` append-only |
| Service Catalog | `service_catalog_items` + `service_form_fields` — typed form fields, required attachments, approval chains, expected hours, default team, auto-ticket creation |
| Assignment | `AssignmentService` — agent/team/department, validates active account + ticket access, `ticket_assignments` history per row |
| Teams | `ticket_teams` + `ticket_team_members` (employees), leader, workload view |
| Tasks | `ticket_tasks` (`XERIN-TSK-…`), dependencies (blocked enforcement), checklists, notes, effort tracking |
| SLA | `ticket_sla_policies` (priority/type/scoped), business-hours-aware deadline math, `ticket_sla_tracking` — response/resolution due, pause on `pending`, warning %, breach flags |
| Escalation | `ticket_escalation_rules` (sla_breach/unassigned/overdue/critical/warning) → reassign, reprioritize, notify; `ticket_escalations` history |
| Approvals | `ticket_approvals` ordered steps from service `approval_chain`; decide() validates role match; rejection requires reason and skips the rest |
| Automation | `ticket_automation_rules` on created/updated/scheduled → assign team, set priority, notify managers, auto-close; `max_runs_per_ticket` loop guard; `ticket_automation_logs` |
| Communication | `ticket_comments` (public / internal notes gated by `ticket.internal_note`), `ticket_attachments` on private managed files |
| Knowledge Base | `knowledge_articles` + `knowledge_categories` — draft/published/archived, `XERIN-KB-…`, view/helpful counters |
| Dashboards | `TicketService::dashboard()` — role-aware real aggregates (group / scoped / mine) |
| Reports | `TicketService::report()` — volume, SLA compliance %, avg first-response & resolution times, reopened, agent workload |

## Security model

- `ticket.view_all` → everything. `ticket.view` → own-company tickets + team queues + own assignments/requests. No permission → own requests only.
- Requester company/branch/department is always taken from the caller's `Employee` record — never from the payload.
- Assignment validates `User::isActive()` and ticket-access permission.
- Internal notes are filtered out of requester-visible comment sets in views and API.
- Attachment downloads go through the shared `ManagedFile` authorization path.
- SLA scan runs server-side via `ticketing:sla-scan` (scheduled every 15 min).

## Permissions (22)

`ticket.view`, `ticket.view_all`, `ticket.create`, `ticket.update`, `ticket.assign`, `ticket.close`, `ticket.reopen`, `ticket.delete`, `ticket.comment`, `ticket.internal_note`, `ticket.attachment.upload`, `ticket.approve`, `task.view`, `task.create`, `task.assign`, `task.update`, `task.complete`, `sla.manage`, `escalation.manage`, `service_catalog.manage`, `ticket_automation.manage`, `ticket_reports.view`, `knowledge_base.manage`

## Scheduled work

| Command | Purpose |
|---|---|
| `php artisan ticketing:sla-scan` | Mark SLA breaches, fire escalation rules, warn-on-threshold. Registered in `TicketingServiceProvider::registerSchedule()` |

## Seed data

- 16 ticket types, 8 categories, 4 SLA policies (Critical 15m/4h, High 1h/8h, Normal 4h/24h, Low 8h/72h — business hours 08–17)
- 10 catalog services incl. `laptop` (manager→it→finance chain) and `new-employee-it-setup`
- Escalation rule: unassigned critical → notify IT manager
- KB: General + IT How-To categories

## Tables (24)

tickets, ticket_types, ticket_categories, ticket_status_histories, ticket_assignments, ticket_comments, ticket_attachments, ticket_tags(+pivot), service_catalog_items, service_form_fields, ticket_approvals, ticket_sla_policies, ticket_sla_tracking, ticket_escalation_rules, ticket_escalations, ticket_automation_rules, ticket_automation_logs, ticket_teams, ticket_team_members, ticket_tasks, ticket_task_dependencies, ticket_task_checklists, ticket_task_notes, knowledge_articles, knowledge_categories

## Routes

Web: `/ticketing` (+`/my`, `/dashboard`, `/catalog`, `/tasks`, `/approvals`, `/kb`, `/admin/*`)
API: `/api/v1/tickets*`, `/api/v1/tasks*`, `/api/v1/service-catalog`, `/api/v1/ticketing/{dashboard,reports}`
