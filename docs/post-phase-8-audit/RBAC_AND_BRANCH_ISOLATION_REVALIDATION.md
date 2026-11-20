# RBAC & Branch Isolation Revalidation

- 253+ permissions across 16 modules; 20+ roles (super-admin, admin, org roles,
  per-module ops roles, Customer, CS Officer, Claims roles, Sales roles…).
- Enforcement: route middleware (`permission:*`), controller `abort_unless(
  can())`, service-layer SoD (claims, pricing approvals), Spatie cache.
- Branch isolation: customers/opportunities/analytics scoped via branch_id;
  P6 test proves DXB user sees 0 TZ orders; ticket/finance lists scope by
  employee branch.
- Customer isolation: portal resolves user→customer, every query scoped
  (IDOR-guarded, tested).
- Department isolation: departments carry company+branch; dept-level data
  scoping is by convention — not globally enforced (accepted risk, MEDIUM).
- Segregation of duties verified: claim investigator≠approver; pricing
  requester≠decider; commission approver recorded; reception routes but
  it-administrator provisions.
- Gaps: MFA not implemented (MISSING, P1); rate-limiting only on public
  intake endpoints; no field-level masking for partner bank_details yet.
