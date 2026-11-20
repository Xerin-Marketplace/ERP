# Updated Module Dependency Map

Foundation: Organization → Identity → (all modules)
Core services shared: NavigationRegistry, AuditService, sequence refs,
branch scoping, Spatie perms, Sanctum.

Commercial → Cargo (handover) → Shipment (packages) → Arrival (shipments) →
Delivery (release-gated orders) → Portal (customer view of all) — linear ops spine.

Finance ← read by Portal (statement) + Sales (collected revenue, analytics,
targets) + Arrival (customs charges) + Delivery (COD) — Finance is sink, not source.

Ticketing ← reused by Portal (customer tickets).

Sales → Commercial (customers/leads/quotations/rate_cards) + Finance (collected)
+ Portal(complaints/claims surfaced in 360).

Phase 9 (Procurement) will depend on: Organization, Identity, Finance
(suppliers/AP/journals), Cargo warehouses (GRN storage), approval patterns,
AuditService — all verified present.
