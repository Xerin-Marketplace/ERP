# PHASE 4 DATABASE CHANGES

Single migration `Modules/Shipment/database/migrations/2026_12_08_000001_create_shipment_tables.php` — 13 new tables, no alterations to existing tables, all FK-constrained:

| Table | Purpose | Key cols / relations |
|---|---|---|
| airlines | carrier master | code uniq, iata/icao, routes json |
| shipments | shipment header | `XER-SHP-*`, origin/dest branch FK, airline FK, parent_id (splits), 21-status lifecycle |
| shipment_packages | allocation history | shipment+package FK, allocated_at/by, removed_at/by+reason (soft history) |
| shipment_events | audit timeline | event + user + meta |
| airline_bookings | `XER-BKG-*` | shipment+airline FK, booking_ref, flight_no, status, confirmed_by/at |
| air_waybills | HAWB/MAWB | awb_number+version uniq, parent (HAWB→MAWB), booking FK |
| shipment_documents | export docs | type, version, path, review_status, uploaded/reviewed_by |
| cargo_manifests | `XER-MNF-*` | shipment+booking+flight FK, reconciliation json, version |
| airport_dispatches | `XER-DSP-*` | vehicle/driver, departed/arrived ts |
| airline_handovers | `XER-AHO-*` | expected/accepted pkgs, rejected+missing json, partial|complete |
| flights | flight records | airline FK, sched vs actual ts, status |
| flight_events | flight audit | event + source (api/manual/airport/provider) |
| shipment_exceptions | `XER-EXC-*` | category, severity, package_ids json, status |

Indexes: unique refs; `shipment_packages(package_id, removed_at)`; `flights(flight_no, scheduled_departure)`; `shipment_events(shipment_id, event)`.
All timestamps UTC. Decimals for weights/values. No destructive ops; `down()` drops only new tables.
