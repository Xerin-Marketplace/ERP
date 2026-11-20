# HR API — new endpoints (web)

| Method | Route | Permission |
|---|---|---|
| POST | /hr/employees/{e}/bank | hr.bank_details.manage |
| POST | /hr/bank/{account}/verify | hr.bank_details.verify |
| GET | /hr/compensation | hr.compensation.view |
| POST | /hr/employees/{e}/compensation | hr.compensation.manage |
| POST | /hr/compensation/{rev}/approve | hr.compensation.approve |
| POST | /hr/compensation/{rev}/reject | hr.compensation.approve |
| GET | /hr/payroll | hr.payroll.view |
| POST | /hr/payroll/periods | hr.payroll.process |
| POST | /hr/payroll/{period}/process | hr.payroll.process |
| POST | /hr/payroll/{period}/approve | hr.payroll.approve |
| POST | /hr/payroll/runs/{run}/pay | hr.payroll.approve |

API v1 exposure for these pillars is on the roadmap.
