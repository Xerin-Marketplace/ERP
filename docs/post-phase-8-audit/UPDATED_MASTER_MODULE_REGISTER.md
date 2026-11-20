# Updated Master Module Register (post-Phase 8)

Counts: **18 domains · 16 enabled modules · ~150 registered features**
Status counts are summarized per module; feature-level detail lives in
UPDATED_FEATURE_COMPLETION_REGISTER.md.

| ID | Domain | Module | Status | Evidence |
|---|---|---|---|---|
| A | Organization | Organization | EXISTS | companies/branches/departments, definitions, scoping, seeders |
| B | Access/Admin | Identity | EXISTS | users/roles/perms/invitations/lifecycle/delete/verify/forced-pwd, 253+ perms |
| B2 | Reception | Reception | EXISTS | workspace + access-request workflows/provisioning (P4 + c964c6e) |
| M | HR/Employee | Employee+HR | EXISTS | employees 360, assignments, onboarding/offboarding, attendance/leave/payroll/performance, bank+comp |
| B3 | Service desk | Ticketing | EXISTS | full ITSM: SLA, teams, escalation, automation, KB, reused by portal |
| K | Finance | Finance | EXISTS→PARTIAL | CoA/journals/GL, AR invoices+payments, AP supplier_invoices, banks/recon, budgets, assets, tax — deep schema; reporting screens thin |
| R | SysAdmin | SystemAdministration | EXISTS | settings, branding, nav overrides, feature flags, maintenance mode, integrations table |
| C | Commercial/CRM | Commercial | EXISTS | customers+addresses+contacts+docs, leads, inquiries, quotations+versions, rate cards, handovers |
| E+F | Cargo/WH | Cargo | EXISTS | pre-alerts, receivings, packages, measures, inspections, repack, warehouses/locations/movements, incidents |
| H | Shipment | Shipment | EXISTS | shipments/pkgs, airlines, bookings, AWB, manifests, handovers, dispatch, flights/events, exceptions |
| I | Arrival/Customs | Arrival | EXISTS | arrivals, reconciliation, import docs, customs cases/charges, agents, collections, batches, release |
| J | Delivery | Delivery | EXISTS | orders, zones/services, drivers, vehicles, dispatch, tracking, POD, returns, collections, COD |
| D+O | Portal/CX | Portal | EXISTS→PARTIAL | dashboard, 360, tracking, statement, tickets, complaints, claims+SoD, feedback, messages, privacy |
| C2 | Sales ops | Sales | EXISTS→PARTIAL | pipeline, opportunities, targets, segments, contracts+versions, partners, commissions, campaigns, approvals, analytics |
| L | Procurement | — | MISSING | no requisition/RFQ/PO/GRN — Phase 9 scope |
| N | Doc centre | (managed_files) | PARTIAL | file store exists; no unified entity linking/versioning |
| P | Notifications | (customer_messages) | PARTIAL | schema + preference gate; no transport providers |
| Q | BI/Reports | (per-module dashboards) | PARTIAL | real-metric dashboards in most modules; no report builder/exports |
| R2 | Platform ops | — | PARTIAL | feature flags exist; no monitoring/backup/DR tooling |

DUPLICATE: none found — Phase 7 deliberately reused Ticketing instead of a
parallel support engine; per-domain doc tables overlap conceptually but serve
distinct lifecycles (flagged in DOC_CENTRE audit).
