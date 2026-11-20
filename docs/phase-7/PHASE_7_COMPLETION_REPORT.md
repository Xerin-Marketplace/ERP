# PHASE 7 COMPLETION REPORT — Customer Experience

## Executive summary
New `Modules/Portal` delivers customer portal + internal service desk: customer-scoped dashboard (real cross-module data), verified tracking milestones, support tickets (reuses Ticketing engine), complaints, claims w/ investigation→approval SoD→settlement, feedback, comms prefs, privacy requests, Customer 360, statement (Finance read-only), service desk dashboard. 6 new tables + 2 alterations. **385/385 tests** (+14 new).

## What was built
| Domain | Implementation |
|---|---|
| Portal auth | `customers.user_id` link; `customerFor()` resolves user→customer; every route customer-scoped |
| Dashboard | Real counts: packages by status, active deliveries, open tickets/complaints/claims, outstanding balance (Finance) |
| Tracking | `trackingTimeline` — package status → customer-safe milestone labels, no internal data |
| Tickets | `XER-TKT-*` via Ticketing reuse; portal replies public-only; staff internal notes hidden from portal |
| Complaints | `XER-CMP-*`, 10 categories, 10-state lifecycle, customer-visible vs internal events |
| Claims | `XER-CLM-*`, 12-state lifecycle — investigation → pending_approval → decide (SoD: investigator ≠ approver) → settlement_pending (requires Finance ref to settle) |
| Feedback | rating 1–5, unique (customer+survey_key) → no dup submissions |
| Comms | `customer_messages` — channel prefs enforced for non-operational; `queued` status never claims delivered |
| Privacy | `XER-PRV-*` — access/correction/export/closure/preferences request register |
| Customer 360 | Read-only aggregation: quotations, packages, deliveries, invoices, tickets, complaints, claims, addresses |
| Statement | Projection over `customer_invoices` (total/received/balance), closing balance — no parallel ledger |
| Service desk | `/service-desk/*` — real KPIs, ticket register + replies, complaint transitions, claim workspace (investigate/decide/settle), privacy register |

## API (`auth:sanctum`, `/api/v1/portal`)
`GET dashboard|tracking|statement`, `POST tickets|claims` — all customer-scoped via `customerFor()`.

## Frontend (Blade)
Portal: `/portal` dashboard, tracking, tickets+thread, complaints, claims, statement, messages. Staff: `/service-desk/dashboard`, tickets, complaints, claims+workspace, customer360, privacy.

## RBAC
11 perms; roles: Customer, Customer Service Officer, Claims Investigator, Claims Approver. SoD enforced server-side.

## Test evidence
`--filter PortalModuleTest` → **14/14** (35 assertions): customer link+dashboard, ticket ref+source, internal-note flag, complaint lifecycle+invalid transition, claim SoD, partial-approval gate, settlement ref gate, feedback dedupe, channel-pref block, privacy ref, statement math, IDOR scoping, 360 aggregation.
Full suite → **385/385**.

## Known limitations
- No WhatsApp/SMS/email provider credentials — `customer_messages` records intent w/ `queued` status; delivery requires provider wiring
- SLA policies exist in Ticketing module but not yet applied to customer tickets (tracking table available)
- Document centre: portal exposes tickets/claims/statement; file-download endpoints for docs = future
- PDF statement export: data ready, renderer not added (no PDF lib)
- Blade frontend (not Next.js); SW partial — consistent Phases 1–6

## Deployment
`php artisan migrate && php artisan db:seed --class='Modules\Portal\Database\Seeders\PortalDatabaseSeeder'`
Link customers to user accounts: `customers.user_id = <user>`.

## Acceptance matrix (abbrev.)
PASS: 1–3 (portal auth+isolation+real dashboard), 4 (Customer 360), 5 (verified tracking), 7–8 (Finance read-only+statement math), 9 (ticket lifecycle via reuse), 11 (complaints lifecycle), 12 (claims SoD lifecycle), 13 (settlement→Finance ref), 15 (auditable comms), 16 (feedback dedupe), 17 (privacy requests), 18 (RBAC+SoD), 21 (385/385), 22 (docs). PARTIAL: 6 (doc list ready, secure download endpoints pending), 10 (SLA infra exists, not yet wired to customer tickets), 14 (returns visible via 360, no customer-initiated return flow), 19–20 (Blade not Next.js, SW partial). FAIL/BLOCKED: none.
