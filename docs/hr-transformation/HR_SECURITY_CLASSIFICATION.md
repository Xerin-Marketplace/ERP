# HR Security Classification

| Class | Data | Controls |
|---|---|---|
| Public-internal | name, position, branch, dept | `employee.view` |
| Confidential | employment records, documents | `employee.profile.view`, `employee.document.*`, permission-gated download |
| Restricted financial | salary, bank, payroll | `hr.compensation.*`, `hr.bank_details.*`, `hr.payroll.*` — encrypted account_number/IBAN, masked display, verify workflow, propose/approve split, audit on every change |
| Highly restricted PII | national_id | `encrypted` cast, `employee.profile.view` gate |
| Restricted disciplinary | cases/actions | `disciplinary.view/manage` |

Every sensitive write logs to `audit_logs` + `employee_events`. No public URLs for documents. Payroll approvals write approved_by/at.
