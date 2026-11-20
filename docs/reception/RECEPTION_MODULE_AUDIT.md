# Reception Module Audit — Phase 4

## Existing and reused (no duplication)
- `employees` + `employee_personal_details` (Employee module) — directory source.
- `attendance_records` (HR) — daily attendance source of truth. Read-only for reception.
- `tickets`, `ticket_categories`, `ticket_teams`, `ticket_team_members`, `ticket_comments`, `ticket_status_histories`, `ticket_attachments` (Ticketing) — ticket source of truth.
- `managed_files` — attachment storage records.
- `receptionist` role (Identity seeder) — extended to spec permission set.

## Added
- `Modules/Reception` — controllers, views, `ReceptionService` (branch-scoped reads).
- `ticket_team_members.availability` + `availability_updated_at` — technician status (available|busy|on_break|offline|on_leave).
- `TicketNumberService::itTicket()` — branch-scoped `IT-{CODE}-{YYYY}-{######}` numbering.
- `TicketService::create()` — `on_behalf_employee_id` + `branch_code` support; requester org context still set server-side.

## Not built (out of scope / future)
- Visitor management (`reception.visitors.*` perms exist, no module yet).
- Ticket assignment by receptionist (deliberately withheld per spec).
