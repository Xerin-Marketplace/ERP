# PHASE 5 HANDOVER CONTRACT — Tanzania Arrival Operations

## Trigger
Shipment reaches `status = arrived` (set via controlled transition `in_transit → arrived`, `actual_arrival` stamped).

## Payload available to Phase 5
| Data | Source |
|---|---|
| Shipment ref, route, status | `shipments` (`reference`, `origin_airport`, `destination_airport`, `actual_arrival`) |
| Package list + refs + custody | `shipment_packages` (active rows `removed_at IS NULL`) → `cargo_packages` |
| Customers | `cargo_packages.customer_id` → `customers` |
| AWB / manifest refs | `air_waybills`, `cargo_manifests` (latest issued versions) |
| Airline + flight | `shipments.airline_id`, `airline_bookings.flight_no`, `flights`, `flight_events` |
| Airline acceptance evidence | `airline_handovers` (accepted/rejected/missing packages, receipt evidence) |
| Weights/pieces | `shipments.total_*`, `cargo_manifests.reconciliation` |
| Open issues | `shipment_exceptions` (status `open`, `package_ids`, `category`) |
| Export docs | `shipment_documents` (type, version, path, review_status) |
| Timeline | `shipment_events` |

## Recommended Phase 5 entry points
- Query `Shipment::where('destination_airport', $tzAirport)->where('status','arrived')`
- Consume `arrival_notification`-style listener on `status:arrived` event (event rows already persisted)
- Branch isolation: destination branch (TZ) officers need `shipment.all_branches` or a new `arrival.view` scope — extend `Shipment::scopeFor` to allow destination-branch read

## Explicitly out of Phase 4
TZ customs clearance, import duty, airport collection, TZ warehouse/distribution, last-mile delivery.
