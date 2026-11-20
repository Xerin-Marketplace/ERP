# PHASE 5 INITIAL AUDIT — TZ Arrival, Import & Customs

## Verified prerequisites
| Phase | Evidence | Status |
|---|---|---|
| 4 shipment | `Modules/Shipment` — shipments (status `arrived`), `shipment_packages` active allocs, `air_waybills` (hawb/mawb), `cargo_manifests` w/ `reconciliation` json, `airline_handovers`, `flights`/`flight_events`, `airlines`, `shipment_events` | OK |
| 3 cargo | `cargo_packages` (status/branch_id/onHold), `warehouses`+`warehouse_locations`+`warehouse_movements` — **branch-scoped, reused for TZ** (no second engine) | OK |
| 2 commercial | `customers` + addresses/contacts — reused for charge allocation + batches | OK |
| 1 org/RBAC | branches DXB+TZ, `currentAssignment` scoping, Spatie | OK |
| Regression | 342/342 before Phase 5 | OK |

## Reused vs new
- **Reused:** Cargo warehouse/location/movement infra (branch_id scoping → TZ warehouse = Warehouse row w/ TZ branch); package identity (`cargo_packages` status extended with `received_tz`); `NavigationRegistry`; `ApiResponse`; ref sequence pattern; `x-app-layout` shell (Blade — repo has no Next.js, deviation documented since Phase 4).
- **New (no prior equivalent):** `shipment_arrivals`, `arrival_events`, `arrival_discrepancies`, `import_documents`, `clearing_agents`, `customs_cases`+`customs_case_events`, `customs_charges`, `airport_collections`, `package_receipts` (unique package_id → dup-physical-receiving guard), `distribution_batches`+items, `release_overrides`.

## Hard rules enforced in code
- `shipment_arrivals.shipment_id` UNIQUE → no duplicate arrival per shipment
- `customs transitionCase` gates: `payment_recorded` needs payment_evidence; `released` needs release_evidence — never auto-approved
- `airport_collections.approve` requires `customs_case.status='released'` + release ref
- `package_receipts.package_id` UNIQUE → package physically received once; re-scans would need movement events
- Distribution batch creation requires `releaseCheck().ready === true`
- Overrides audited (user/reason/check/timestamp)

## Risks
- No TRA/customs API credentials → manual-first with evidence fields + event log; adapter boundary left in `transitionCase`/`caseEvent`
- File upload field on `import_documents` — S3 wiring deferred (Phase 3/4 posture)
- `charges` are records + approval flags only — no ledger posting (per spec: no duplicate accounting engine)
- `releaseCheck` resolves arrival via latest active `shipment_packages` row — assumes one active shipment per package (enforced by Phase 4 allocation)
