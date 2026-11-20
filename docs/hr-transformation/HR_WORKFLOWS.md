# HR Workflows (new)

## Salary revision
propose (manage perm) → pending → approve (approve perm, ≠requester) → old approved → superseded → audit + timeline event.

## Bank details
save (manage) → pending verification → verify/reject (verify perm) → masked display, audit.

## Payroll cycle
open period → process (draft runs from approved compensations, unique per employee+period) → approve period (≠processor unless super-admin) → mark each run paid → reversal only via auditable `reverse()`.
