# Performance Audit

- Eager loading used in directory/show/ticket queries (`with([...])`) — no obvious N+1 in hot paths reviewed.
- Indexes: work_date/status, ticket status+priority, employee unique numbers — adequate for current scale.
- Pagination on all list endpoints (20-25/page).
- Potential hotspots for cargo domain: package-level event timelines, dashboard aggregations across ~150-table schema — design indexes up-front (employee+date, shipment+status composites).
- Number generators use row-lock pattern — safe but serializes bursts; acceptable.
- Dashboard KPIs run live counts — fine at current volume; add cached aggregates when cargo tables grow.
