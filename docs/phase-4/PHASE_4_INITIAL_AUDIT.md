# PHASE 4 INITIAL AUDIT — Shipment & Airline Operations

## Verified prerequisites (repository evidence)

| Phase | Evidence | Status |
|---|---|---|
| 0 audit | `docs/phase-0/` module inventory | OK |
| 1 core/org | `Modules/Organization` (companies, branches DXB+TZ, departments), Identity (users, employee_assignments, RBAC/Spatie), locale EN/SW middleware | OK |
| 2 commercial | `Modules/Commercial` — customers (extends Finance table), leads, inquiries, quotations, `commercial_handovers` | OK |
| 3 cargo ops | `Modules/Cargo` — pre-alerts, receivings, `cargo_packages` (status: stored/allocated/dispatched/on_hold), warehouses+locations, movements, inspections, weighings, incidents, `cargo_events`, Code-128 labels + scan endpoints | OK |
| Regression | 342/342 tests pass (incl. 13 new Phase 4 tests) | OK |

## Reusable components
- `App\Support\ApiResponse` (success/created/paginated/error) — used by Shipment API
- `App\Services\Navigation\NavigationRegistry` + `NavItem` — nav entry "Shipment & Airline"
- `currentAssignment()->branch_id` user scoping pattern — `Shipment::scopeFor`
- Sequence ref pattern `XER-XXX-YYYY-######` (locked `orderByDesc`)
- `x-app-layout` Blade + Tailwind + Alpine shell (repo uses Blade, not Next.js — documented architecture deviation)
- `CargoPackage::onHold()`, status field, `branch_id` — eligibility checks

## Gaps closed by Phase 4
- No shipment/consolidation/booking/AWB/manifest/dispatch/handover/flight tables existed — all new, none duplicated
- Airlines: no carrier master existed → new `airlines` table (no supplier/customer duplication)
- Export documents: `shipment_documents` added (generic, per-shipment) — complements Cargo receiving attachments

## Risks & notes
- Frontend stack is Blade/Tailwind, NOT Next.js — spec mismatch documented; reused existing shell to preserve architecture
- `cargo_packages` has no `is_hold()` public API → used `status === 'on_hold'` via `onHold()` model method where present; eligibility list is explicit (`stored|ready|inspection_passed|weighed`)
- No live airline/tracking API credentials → manual flight events with `source` field + audit; never simulated
- `shipment_packages` allows removed history (soft `removed_at`) — package identity preserved across splits

## Recommended sequence (executed)
4.0 audit → 4.1 shipment foundation+API → 4.2 allocation/consolidation/split → 4.3 airline+booking → 4.4 AWB+docs+readiness → 4.5 manifest+dispatch+handover → 4.6 flights+exceptions+tracking → 4.7 regression+docs
