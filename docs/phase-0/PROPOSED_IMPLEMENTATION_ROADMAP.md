# Proposed Implementation Roadmap (dependency-aware)

## Now → Dec 2026 launch

**P1 — Foundation hardening (immediate, small)**
1. Rotate DB credentials; CI pipeline (tests + Postgres); backup verification; Redis install.
2. Central `BranchScope` + approval engine skeleton (needed by everything after).
3. Frontend decision sign-off (keep Blade recommended).

**P2 — Cargo core (largest block, the business)**
4. Customer master + CRM inquiry → Quotation (rate card) — unlocks revenue pipeline.
5. Cargo domain: Receiving → Identification → Weighing → Packaging → Shipment → AWB/Manifest.
6. Flight ops + arrival + import docs + customs clearance (TZ).
7. Distribution centre → dispatch → last-mile → POD → closure.
8. Package-level tracking + milestone events + notifications (WhatsApp/SMS adapters).

**P3 — Supporting build-out**
9. Fleet: drivers, vehicles, routes, trips, assignment.
10. Procurement: suppliers→PO→GRN→vendor payments; inventory (company stock, NOT cargo).
11. Payroll→GL posting; multi-currency conversion service; invoicing PDF.
12. Customer portal (track + invoices); notification centre; OpenAPI spec.

**P4 — After launch**
13. Report builder, KPI mgmt, compliance/risk/policy, promotions, commissions, facilities, fuel.

## Effort honesty
Domain B alone ≈ the size of everything built so far. With the existing foundation (org/RBAC/branch scoping/docs) reusable, a focused 5–6 month runway to Nov 30 is feasible — but only if cargo work starts now and scope is sliced as above.
