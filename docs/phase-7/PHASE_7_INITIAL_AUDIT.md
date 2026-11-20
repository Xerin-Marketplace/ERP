# PHASE 7 INITIAL AUDIT — Customer Experience

## Verified prerequisites
| Need | Evidence | Status |
|---|---|---|
| Ticketing engine | `Modules/Ticketing` — Ticket (9-state transitions), TicketComment (`is_internal`, `publicComments()`), SLA policies/tracking, escalations, attachments — **reused** via `customer_id`+`source` columns, NOT duplicated | OK |
| Customers | `customers` + addresses/contacts (Phase 2) — `user_id` column added for portal login link | OK |
| Finance | `customer_invoices` (total/received/status), `payment_records` — read-only source of truth for statements | OK |
| Tracking | `cargo_packages.status` lifecycle DXB→TZ→delivered → verified customer milestones | OK |
| Customer docs | `customer_documents` (Commercial), `shipment_documents`, `import_documents` — all have review_status | OK |
| Complaints/claims | **None existed** — greenfield `complaints`+`cargo_claims` | NEW |
| Portal | **None existed** — `customers.user_id` link + portal routes | NEW |

## Reuse decisions
- Customer support tickets = `tickets` rows with `source='customer_portal'` + `customer_id` + `related` JSON (shipment/package/order/invoice) — inherits Ticketing's state machine, comments, SLA, escalation for free
- Statement = projection over `customer_invoices`, closing balance = Σ(total − received) — no parallel ledger
- Internal notes never leak: portal ticketShow loads `comments()->where('is_internal', false)` only

## Hard rules in code
- IDOR guard on every portal route (`ticket->customer_id === current customer's id`)
- Claim SoD: `decideClaim` aborts if `investigator_id === deciding user`
- Partial approval requires amount < claimed; full approval sets approved=claimed
- Settlement: `requestSettlement` only after approval; `markSettled` requires Finance ref — **no auto payment creation**
- Feedback unique (customer_id + survey_key); messages respect channel prefs for non-operational; message status `queued` ≠ `delivered`
