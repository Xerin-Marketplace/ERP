# Updated Implementation Roadmap

## MUST_FIX_BEFORE_PHASE_9
- (none blocking) — regression suite must be re-green after the
  `users.delete` seed-order fix applied during this audit (verified in run).

## REQUIRED_DURING_PHASE_9
- Procurement: supplier lifecycle, requisitions, RFQ, POs+versions, GRN
  (partial receiving), 3-way match to supplier_invoices, payments via
  payment_records, supplier statement read-only from Finance.
- Reuse: approval patterns, transitions maps, branch scoping, AuditService,
  NavigationRegistry, sequence refs (XER-PO-, XER-GRN-, XER-RFQ-).

## CAN_RUN_IN_PARALLEL
- Document-centre convention decision (managed_files polymorphic vs per-domain).
- SMS/WhatsApp provider selection + credentials (management → then thin adapter).
- SPF/DKIM/PTR DNS work for mail deliverability (ops task, not code).

## POST_PHASE_9
- Asset/inventory linkage to purchases; budget consumption vs budgets table;
  supplier performance analytics; customer-statement PDF export;
  portal document download endpoints; SLA on customer tickets.

## PRODUCTION_READINESS
- S3 bucket + signed URLs; payment gateway (mobile money) with webhook
  idempotency; Redis for queues/cache on server; monitoring/backup strategy;
  mobile app decision.

## MANAGEMENT_DECISION_REQUIRED
- SMS/WhatsApp provider choice & budget; mobile money provider; S3 host;
  whether document centre becomes unified polymorphic store (migration effort)
  or stays per-domain (faster, consistent with current architecture);
  Redis provisioning on the VPS.
