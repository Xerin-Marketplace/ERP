# Database & Data Integrity Audit

- 239 tables; FK constraints used broadly; unique constraints on refs,
  composite uniques (contract_versions, commission source dedupe, acquisitions).
- Financial fields decimal; timestamps UTC (Laravel default); branch-local
  display only.
- Soft deletes: users only (new). Other masters use status lifecycle —
  consistent with "no destructive" policy.
- SQLite test schema = production schema (same migrations run in tests) —
  a Postgres-only ordering bug in cargo migration was found on the server and
  fixed (`69d3060`); audit scanned all module migrations for forward-FK
  references — no other occurrences.
- Data integrity controls verified: controlled TRANSITIONS maps (tickets,
  complaints, claims, contracts, partners, commissions, delivery orders),
  evidence gates (signature before contract active; finance ref before
  commission paid; release-check before delivery order).
- No destructive migrations in history.
- Risk: `sales_opportunities.contract_id` is a plain nullable bigint without
  FK (added at create-time before commercial_contracts existed in same file —
  acceptable, flagged low).
