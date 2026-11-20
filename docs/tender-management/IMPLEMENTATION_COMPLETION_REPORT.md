# TENDER MANAGEMENT — PHASE 1 REPORT

## Implemented (Modules/Tender — 17 tables)
- tender_opportunities (unique ref+buyer duplicate guard, stages, deadlines,
  securities requirements, buyer types: public/private/international/ngo;
  categories: goods/works/services/consultancy/framework/jv/subcontract)
- tender_team_members, tender_documents (versioned), tender_requirements
  (compliance matrix w/ mandatory flag), tender_company_qualifications
  (expiry-tracked corporate pack)
- tender_assessments (Bid/No-Bid: commercial 30%, technical 25%, compliance
  20%, strategic 15%, risk 10%; threshold 65; NO_BID requires reason)
- tender_boqs + tender_boq_items (bcmath decimal-safe totals: direct,
  overhead%, contingency%, profit%, tax%; immutable original desc/qty)
- tender_quotations (supplier comparison, technical compliance flag)
- tender_pricing_scenarios (versioned)
- tender_securities (tender/performance/advance/retention/warranty;
  expires vs required_until validity check in SubmissionGate)
- tender_approvals, tender_submissions (verified evidence REQUIRED —
  upload alone ≠ submitted), tender_events, tender_contracts +
  milestones, tender_transitions (immutable history)

## Engine
- TenderWorkflow — 22-stage machine; API cannot skip; gates:
  preparation needs bid decision, submission_ready needs all approvals,
  submitted needs verified evidence, contract_signing needs contract
- SubmissionGate — readiness checklist (decision, mandatory compliance,
  BOQ, security validity, approvals, deadline)
- Roles: tender-manager, tender-officer, quantity-surveyor,
  compliance-officer + 11 tenders.* perms

## UI (Blade) — dashboard (pipeline/deadlines/values), opportunities
register+list, tender workspace (bid decision, readiness, BOQ entry+totals,
events, award→contract)

## Tests: 11/11 — scoring math, reason-required NO_BID, stage anti-skip,
bcmath totals, immutable originals, checklist gate, security validity,
full pipeline register→award.

## Deferred (tracked): Excel BOQ import, supplier RFQ screens, NeST
structured import (no invented API), AI doc analysis, RACI tasks UI,
milestone→Finance invoicing, multi-currency, Swahili labels.
