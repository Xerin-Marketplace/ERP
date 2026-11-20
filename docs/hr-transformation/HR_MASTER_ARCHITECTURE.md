# HR Master Architecture

Modules/HR + Modules/Employee share the Employee aggregate:

- **Employee module** owns: identity, personal details, placement, assignments, transfers, documents, lifecycle, on/offboarding.
- **HR module** owns: attendance, leave, overtime, shifts, recruitment, performance, training, disciplinary, welfare — **plus new**: bank accounts, compensation, payroll.

Cross-module via `Employee` relations (bankAccounts/compensations/payrollRuns) — no duplicate employee tables.

Services: BankDetailsService (mask/verify), CompensationService (revision chain), PayrollService (period→process→approve→pay/reverse), HRNumberService (XERIN-PAY-*).

UI: `/hr/*` module pages + employee profile tabs; System Admin control center shows workforce stats.
