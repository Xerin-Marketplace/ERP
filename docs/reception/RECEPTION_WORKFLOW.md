# Reception Workflows

## Attendance monitoring
1. Reception opens `/reception` or `/reception/attendance` — sees branch records for today, filter by date/department.
2. Statuses come from HR `attendance_records` — reception cannot edit. "Unrecorded" ≠ absent: employees without a record are counted separately until HR rules evaluate them.
3. CSV export requires `reception.attendance.export` (not granted by default).

## Ticket on behalf of employee
1. `/reception/tickets/create` — pick employee (own branch only), category, priority, subject, description, location, attachments (PNG/JPG/PDF/MP4 ≤20MB).
2. `TicketService::create()` generates `IT-{BRANCH}-{YYYY}-{######}`, sets requester employee + branch server-side, audits `ticket.created`.
3. Track at `/reception/tickets/{ticket}` — status history, public technician updates, add comment/attachment.
4. Technical resolution remains IT-owned; reception relays public updates.

## Technician availability
`/reception/technicians` lists ticket_team_members with availability + active ticket count. Status is technician-managed data, not inferred.
