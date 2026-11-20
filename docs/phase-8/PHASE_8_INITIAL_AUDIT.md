# PHASE 8 — Initial Audit (actual repository, Laravel modular monolith)

## Verified reusable (Phase 0–7)
| Area | Reused |
|---|---|
| CRM | `Modules/Commercial` — leads (`XER-LD`), inquiries, quotations, rate_cards (Phase 2) |
| Customer | `customers` master (company/branch scoped) — no duplicate customer records |
| Finance | `customer_invoices`, `payment_records` — source of truth for invoiced/collected |
| HR | employees via `users.employee` — sales officers not duplicated |
| RBAC | Spatie permissions; branch isolation via `customers.branch_id`/opportunity `branch_id` |

## Missing (built in Phase 8)
Pipeline stages (configurable), opportunities, sales targets, customer segments,
acquisition attribution, commercial contracts + versioning, pricing approvals,
agents/brokers (none existed), commission rules/records/settlement, campaigns,
pricing simulation, revenue analytics distinguishing pipeline/quoted/invoiced/collected.

## Design decisions
- **Agents & brokers**: single `commercial_partners` table with `type` discriminator
  (shared fields identical; separate refs XER-AGT-/XER-BKR-, separate directories).
- **Commissions**: unique(beneficiary, source) → DB-level duplicate prevention;
  `requires_collected` flag blocks commission on uncollected revenue; settlement
  requires a Finance reference — no parallel ledger.
- **Contracts**: `contract_versions` — historical terms preserved; signature
  evidence required to reach `active` (unsigned ≠ executed).
- **Revenue definitions**: pipeline (open opps), weighted, quoted (quotations),
  invoiced & collected (Finance) reported separately, never conflated.
