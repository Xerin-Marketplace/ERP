# PHASE 6 HANDOVER CONTRACT — Last-Mile Delivery

## Entry point
`GET /api/v1/batches/{id}/handover` (ArrivalApiController) → marks batch `handed_over`, returns payload.

## Payload
```json
{ "batch_reference": "XER-DBT-2026-000001",
  "customer": {"id","code","name","phone","email"},
  "zone": "...",
  "packages": [{"id","reference","status","customer_id","weight","release":{...}}],
  "handed_over_at": "..." }
```

## Eligibility guarantee
`createBatch()` rejects any package failing `releaseCheck` (physically_received, customs_released, charges_settled, not_on_hold, no_open_discrepancy). Phase 6 receives only ready packages.

## Available to Phase 6
- `distribution_batches` + items, `release_overrides` audit
- Package custody: `cargo_packages.status` (`received_tz`), `package_receipts` (receiving officer/location/time), warehouse movements (Cargo module)
- Customer contacts/addresses: Commercial module
- Arrival + customs evidence chain: `shipment_arrivals` → `customs_cases` → `import_documents`

## Out of Phase 5 scope
Driver dispatch, route planning, POD, failed delivery, returns, delivery charges.
