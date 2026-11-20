# PHASE 8 — Completion Report

## Delivered — `Modules/Sales`
- **Pipeline**: configurable `pipeline_stages` (9 seeded, position+probability),
  opportunities `XER-OPP-`, stage moves inherit stage probability, won/lost
  (lost requires reason), weighted pipeline, monthly forecast (labelled estimate).
- **Targets**: company/branch/officer/agent × monthly/quarterly/annual ×
  revenue/new_customers/shipments/contracts. Revenue achievement = **collected**
  (Finance received_amount), not quotes.
- **Contracts** `XER-CON-`: 10-status controlled lifecycle, signature evidence
  required before `active`, versioned terms (`contract_versions`, one current),
  renewal creates a new contract, expiry flag.
- **Agents/Brokers** `XER-AGT-/XER-BKR-`: 8-status register→verify→approve→active
  lifecycle; performance from real acquisitions + commission records.
- **Commissions** `XER-CMS-`: configurable rules (revenue_pct/per_shipment/
  per_acquisition/tiered), `requires_collected` gate, unique(beneficiary+source)
  dup prevention, lifecycle calculated→…→payable→paid; **paid requires Finance
  settlement ref**; reversal supported.
- **Pricing simulation**: rate-card precedence (customer-category card first),
  chargeable = max(gross, volumetric), minimum-charge enforcement, campaign
  discount (validity+usage-limit checked), explanation string. Read-only.
- **Pricing approvals** `XER-PRA-`: request→decide, requester cannot approve
  own request (SoD, tested).
- **Campaigns** `XER-CAM-`: validity window, usage limit, percent/fixed w/ cap.
- **Analytics**: pipeline / weighted / quoted / invoiced / collected / outstanding
  / commission-cost reported as distinct concepts from real sources.

## API `/api/v1/sales` (Sanctum)
dashboard · forecast · analytics · opportunities (CRUD/move) · simulate ·
partners/{id}/performance

## Security
15 perms; roles Sales Officer / Commercial Manager / Finance Reviewer; SoD on
pricing approvals & claim-style commission approval; branch scoping on
opportunities/analytics; unsigned contracts can't activate (tested); commission
on uncollected revenue blocked (tested); settlement w/o Finance ref blocked (tested).

## Honest gaps
- Next.js not adopted — Blade/Tailwind (consistent with repo).
- Contract e-signature/document upload: evidence is a reference string; Document
  module wiring pending. Bank details field exists, restricted-view UI pending.
- Tiered commission basis falls back to fixed_amount (tiers table not modelled).
- Forecast is naive (close-date weighted pipeline); no historical-conversion model.
- SW translations partial.

## Tests
`./vendor/bin/phpunit Modules/Sales/tests` → **16/16, 36 assertions**
Full regression: see git log / TEST note — run alongside suite.
