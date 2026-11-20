# Phase 1–8 Independent Verification

| Phase | Reported | Verified status | Key evidence | Gaps |
|---|---|---|---|---|
| 1 Organization | done | EXISTS | companies/branches/departments tables, branch scoping, 12 depts×branch seeded `53a405a` | — |
| 2 Commercial/CRM | done | EXISTS | leads, inquiries, quotations+events+items, rate_cards, customers/addresses/contacts/docs, commercial_handovers | supplier mgmt minimal (Finance-side only) |
| 3 Cargo Receiving | done | EXISTS | pre_alerts, receivings, packages (QR ref), measurements, inspections, repackagings, warehouses/locations/movements, incidents | label printing is view-level only |
| 4 Shipment | done | EXISTS | shipments, shipment_packages, consolidation/split, airlines, bookings, AWB, manifests, handovers, dispatches, flights/events, exceptions | no live airline feeds |
| 5 Arrival/Customs | done | EXISTS | shipment_arrivals, manifest recon, import_documents, customs_cases/charges/events, clearing_agents, airport_collections, distribution_batches, release_overrides | no TRA integration (documented) |
| 6 Delivery | done | EXISTS | delivery_orders/packages, zones, services, drivers, vehicles, assignments, attempts, events, PODs, returns, collections, COD | no routing provider; driver app API-only |
| 7 Portal/CX | done | EXISTS | portal dashboard/tracking/statement, customer tickets via Ticketing reuse, complaints, claims w/ SoD, feedback, messages, privacy | SLA not applied to customer tickets; doc-download endpoints pending |
| 8 Sales/Commercial | done | EXISTS | pipeline_stages, sales_opportunities, targets, segments, acquisitions, contracts+versions, partners, commission_rules/records, campaigns, pricing_approvals, simulate | tiered commission bands not modelled; contract e-sign = evidence ref only |

## Post-Phase-8 additions (verified)
- Reception Access Management domain (public intake→verification→workflow
  approvals→provisioning) — `c964c6e`, 26 tests.
- User management hardening: delete (soft), verify+activate, temp-password
  generation + forced change, onboarding tracker — `eb2e044`/`d82a7ad`/`6f9df23`.
- Department seeding per branch; cargo Postgres migration fix.

## Test verification
Reported baseline was 401 green @ `d64cabf`. Re-executed post-fix during this
audit (full suite, memory_limit=512M) — see completion report for the final
number. One regression found & fixed during audit: `users.delete` permission
ordering in `RolePermissionSeeder` (seed-order dependency).
