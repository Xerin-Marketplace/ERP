# Phase 2 Completion Report — Customer, CRM, Inquiry & Quotation
Date: 2025-12-06 · Branch: main · Tests: 317 passing (was 306 + 11 new)

## Modules implemented
- **Modules/Commercial** (new nwidart module) — owns all commercial sub-domains:
  Customers, Leads/CRM, Inquiries, Cargo Assessment, Rate Cards/Pricing,
  Quotations, Customer Acceptance, Commercial Handover.

## Existing modules reused (no duplication)
- `customers` table **extended in place** — Finance already owned it (AR
  counterparty). Phase 2 added CRM fields via ALTER, not a parallel table.
- Spatie Permission, SequenceService convention (XER-XXX-YYYY-######),
  NavigationRegistry, ApiResponse, branch `scopeFor` pattern, app shell.

## Database changes
- 1 migration `2026_12_06_000001_create_commercial_tables`:
  ALTER `customers` (+19 CRM cols, soft deletes); CREATE customer_contacts,
  customer_addresses, customer_documents, leads, lead_activities, inquiries,
  inquiry_activities, inquiry_attachments, cargo_assessments, rate_cards,
  quotations, quotation_items, quotation_events, commercial_handovers.

## APIs (/api/v1, Sanctum + permissions)
customers, leads (+/convert), inquiries, quotations (+/{action}: submit|approve|send),
rate-cards, pricing/quote (chargeable weight + breakdown), commercial-handover.

## Frontend (Blade, existing design system)
Commercial dashboard (real KPIs + pipeline/source bars), customer directory +
360 profile + form, lead pipeline with stage transitions + convert,
inquiry list/detail/form + cargo assessment + timeline, quotation
list/detail/form + printable document + event/version history, rate-card
manager with approve-activate. Public signed quotation link `/q/{token}`
(Accept/Decline, bilingual labels, rate-limited).

## Security / critical scenarios verified by tests
- Lead→customer conversion reuses existing customer by phone/email (no dup).
- Quotation pricing_snapshot immutable; rate-card changes don't alter it.
- Sales Officer role lacks `quotation.approve` (403 on self-approval).
- Expired/non-open quotations cannot be accepted (422).
- Exactly ONE commercial handover per accepted quotation (unique FK + firstOrNew).
- Inquiry/lead/customer/quotation lists branch-scoped via `scopeFor`.
- Public link: token-gated, throttled, records 'viewed' once.

## Permissions seeded (idempotent)
20 permissions (commercial.*, customer.*, lead.*, inquiry.*, pricing.*, quotation.*);
roles: Sales Officer, Sales Manager; super-admin gets all.

## Known limitations
- Quotation "PDF" = printable HTML (browser→PDF); no PDF lib installed.
- WhatsApp/SMS/email sends are template-ready but not wired to providers
  (Notification Management phase).
- Approval workflow = single-step permission gate; configurable multi-level
  engine deferred.
- Customer documents upload endpoint present in schema; UI upload deferred.
