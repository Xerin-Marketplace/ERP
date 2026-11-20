# HR RBAC Matrix (new permissions)

| Permission | super-admin | hr-administrator | payroll-officer | payroll-approver | company-admin | employee |
|---|---|---|---|---|---|---|
| hr.bank_details.view/manage/verify | ✓ | ✓ | ✓ | — | — | — |
| hr.compensation.view/manage | ✓ | ✓ | ✓ | view only | — | — |
| hr.compensation.approve | ✓ | ✓ | — | ✓ | — | — |
| hr.payroll.view/process/export | ✓ | ✓ | ✓ | view only | — | — |
| hr.payroll.approve | ✓ | ✓ | — | ✓ | — | — |

Separation of duties enforced in code:
- Compensation: requester **cannot** approve own revision.
- Payroll: processor **cannot** approve own period (unless super-admin).
- Bank account numbers: encrypted at rest, masked (`****8901`) in all views; full value never rendered.
- New roles seeded: `payroll-officer`, `payroll-approver`.
