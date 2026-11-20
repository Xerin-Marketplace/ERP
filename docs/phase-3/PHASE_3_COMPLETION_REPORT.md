# Phase 3 Completion Report — Cargo Receiving & Dubai Warehouse
Date: 2025-12-07 · Branch: main · Tests: 329/329 passing (864 assertions)

## Modules implemented
- **Modules/Cargo** (new nwidart module): pre-alerts, physical receiving,
  package registration & identification, weighing/measurement, inspection,
  repackaging, warehouses/locations, custody movements, incidents.

## Existing reused
- `customers` (Finance/Commercial master), `branches` (DXB), `commercial_handovers`
  (Phase 2 links — no physical event created from a handover), Spatie RBAC,
  `scopeFor` branch isolation, sequence convention, app shell.

## Database changes — `2026_12_07_000001_create_cargo_tables`
cargo_pre_alerts, cargo_receivings, cargo_packages, cargo_events,
cargo_measurements, cargo_inspections, cargo_repackagings, warehouses,
warehouse_locations, warehouse_movements, cargo_incidents (11 tables).

## Sequences
XER-PA-* pre-alerts · XER-RCV-* receivings · **XER-{BRANCH}-PKG-YYYY-######**
packages (branch-scoped, race-safe) · XER-MOV-* movements · XER-INC-* incidents.

## Key behaviors verified by tests (12 new)
- Branch-scoped unique package refs; package count tracked per receiving.
- `short`/`excess`/`unidentified` outcomes auto-flag incidents — partial ≠ full.
- Unidentified cargo stays unlinked until authorized identifyPackage() — never guessed.
- Reweigh preserves full measurement history; chargeable = MAX(gross, volumetric) w/ configurable divisor.
- Inspection `compliance_review`/`repack_required` → package on_hold + incident; hold requires `cargo.inspection.approve` release.
- Repackaging stores immutable before_snapshot (weight/dims/type).
- Warehouse movements record from→to custody; location capacity enforced.
- Duplicate scans flagged via `meta->duplicate` (30s window), not re-transacted.
- Package `scopeFor` branch isolation: TZ-only user sees zero DXB packages.

## API — /api/v1/cargo/* (Sanctum + perms)
dashboard, pre-alerts, receiving (+confirm), packages (+scan/measurements/inspection/move), incidents.

## Frontend (Blade)
Cargo dashboard (12 real KPIs + status breakdown), pre-alerts, receiving list +
session page (register packages inline, confirm with outcome), package directory
+ detail (weigh/reweigh, inspection checklist, move, hold release, identify,
timeline), printable label w/ **pure-PHP Code-128 barcode** (no lib needed),
warehouse map w/ occupancy bars + location manager, incidents board w/ resolve.

## Permissions/roles seeded (idempotent)
27 permissions; roles: Cargo Receiving Officer, Warehouse Officer, Cargo
Inspector, Warehouse Manager (all), super-admin. DXB-MAIN warehouse +
8 starter locations seeded (idempotent).

## Known limitations
- Photo upload fields exist in schema (managed_files integration deferred).
- Notifications not yet wired to WhatsApp/SMS providers (templates ready).
- No hardware scanner driver — scan endpoint accepts events from any client;
  USB/camera scanners work via the package ref input.
- Packaging materials inventory tracking = JSON log (no second inventory system
  per spec); full Inventory module deferred.
