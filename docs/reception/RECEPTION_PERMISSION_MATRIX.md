# Reception Permission Matrix

| Permission | receptionist | super-admin |
|---|---|---|
| reception.dashboard.view | yes | yes |
| reception.attendance.view* | yes | yes |
| reception.attendance.export | no (explicit grant) | yes |
| reception.employees.view_directory | yes | yes |
| reception.tickets.create/view/view_branch/track | yes | yes |
| reception.tickets.comment / attachments.upload | yes | yes |
| reception.it_availability.view | yes | yes |
| reception.reports.view/export | no (explicit grant) | yes |
| hr.compensation.*, hr.bank_details.*, payroll | NO | yes |
| attendance.manage / corrections | NO | yes |

Enforcement: `can:` middleware per route + `ReceptionService::scopeBranch()` resolves the user's employee branch; super-admin/employee.view_all may pass `?branch_id=`. Ticket store aborts 403 when target employee is another branch.
