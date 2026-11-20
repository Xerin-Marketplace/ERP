# PHASE 6 INITIAL AUDIT — Last-Mile Delivery

## Verified prerequisites
| Need | Evidence | Status |
|---|---|---|
| Release-ready cargo | `Modules/Arrival` — `releaseCheck()` gates (received/customs/charges/hold/discrepancy) — reused as the eligibility gate for order creation | OK |
| Packages/custody | `cargo_packages` — status extended with `out_for_delivery`/`delivered`/`collected`/`received_tz` | OK |
| Customers + addresses | `customers`, `customer_addresses` (city/country/address/is_primary) — reused, no duplicates | OK |
| Batches | `distribution_batches` (Phase 5) linkable via `batch_id` | OK |
| Drivers/vehicles/fleet | **None existed** — `drivers` + `vehicles` created (no fleet module to duplicate; documented necessity) | NEW |
| Routing provider | None configured → `distance` pricing flagged `estimated`, manual route workflow; adapter boundary left in `price()` | DEFERRED |
| Mobile app | No Flutter repo in project → driver flows exposed via `/api/v1` endpoints (availability/location/respond/confirm) + web | API-READY |
| Finance | charges/COD recorded with reconciliation + refs — no second ledger | OK |

## Hard rules in code
- `releaseCheck().ready` required to put a package on an order; package can't be on two active orders; `lockForUpdate` + transaction on create/assign/POD
- `DeliveryAssignment` unique active (offered/accepted) per order; reject returns order to `unassigned` queue, never silently reassigned
- Driver eligibility: verified + licence valid + available/busy + same branch
- Vehicle: active + insurance/inspection valid + package capacity check
- `proof_of_deliveries.order_id` UNIQUE → no duplicate POD; OTP/signature gates per service config
- Driver location only while online/on-delivery (offline rejected); 15s duplicate-event dedupe; `track()` marks stale (>10min) location as unavailable — never presented as live
- COD reconcile requires payment ref; driver note ≠ confirmed payment
- Returns: one active return per order; warehouse receipt restores `received_tz` custody, preserves package identity

## Risks
- No real Flutter app / routing provider credentials → marked PARTIAL; APIs designed so a mobile client can consume them
- Blade frontend, not Next.js (deviation documented since Phase 4)
