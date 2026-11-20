# Cross-Module Integration Audit

Pattern: modules integrate via **direct Eloquent reads into source-of-truth
tables** (documented design rule — no duplicate masters) + branch/customer
scoped queries + controlled transition maps + transactions on approvals.

| Chain | Status | Evidence | Issue |
|---|---|---|---|
| A Customer→Cargo | WIRED | quotations.customer_id → commercial_handovers → cargo_receivings.customer_id → cargo_packages.customer_id | handover→receiving linkage verified in P3 tests |
| B Cargo→Shipment | WIRED | shipment_packages pivot, consolidation/split services (P4, 13 tests) | — |
| C Departure→Arrival→Customs | WIRED | shipment_arrivals→customs_cases/charges→release gate→distribution_batches (P5, 71 tests) | — |
| D Release→Delivery→POD | WIRED | release-ready gate consumed by delivery order creation; POD unique per order (P6, 16 tests) | — |
| E Quotation→Invoice→Payment | PARTIAL | customer_invoices/payment_records (Finance) + portal statement reads them; invoice *generation* from accepted quotation is manual (no auto-pipeline) | no quotation→invoice automation |
| F Event→Tracking→Notification | PARTIAL | shipment/delivery events feed portal tracking timeline; `customer_messages` records queued intent but **no transport delivers** | providers unwired |
| G Org→User→Role→Scope→Audit | WIRED | branch scoping tested in P6 (DXB sees 0 TZ); audit_logs table + AuditService used throughout | department-level isolation is convention, not enforced globally |

## Structural findings
- No central event bus: modules call each other's services/models directly.
  Consistent with monolith design; acceptable but means notification triggers
  are per-module, not systemic.
- `Ticket` extended with customer_id+source (P7) — single ticket engine, good.
- Commission records link to source via source_type/source_id + unique key —
  clean dup-prevention.
