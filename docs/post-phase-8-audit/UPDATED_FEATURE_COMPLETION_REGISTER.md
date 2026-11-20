# Updated Feature Completion Register (condensed)

Full feature trace = module tests + routes. Statuses: EXISTS/PARTIAL/MISSING/BLOCKED.

- AuthN/Z: users, roles, 253+ perms, invitations, activation, forced pwd change,
  delete, sessions revoke, login history, security events — EXISTS
- Org: companies, branches(+types), departments(+definitions, per-branch seed),
  locations, assignments — EXISTS
- Employee/HR: master, personal docs, bank (masked), compensation, payroll runs,
  onboarding/offboarding, transfers, attendance(+devices/corrections), leave,
  shifts, overtime, recruitment(+vacancies/applications/interviews), performance,
  training, disciplinary, welfare, announcements — EXISTS
- Ticketing: types/categories/teams/SLA policies+tracking/escalation/automation/
  approvals/tasks+deps/KB/attachments/comments(public+internal)/history — EXISTS
- Finance: CoA, journals+lines, GL, AR invoices+lines+allocations, AP
  supplier_invoices+lines, payments, banks+recon+transactions, budgets+lines,
  assets+depreciation, tax cats/rates/transactions, FX rates, periods/years,
  approvals, expense categories+claims — EXISTS (PARTIAL: reporting UI, payment
  verification workflow vs ops events)
- Reception: workspace + access requests/verifications/workflows/versions/
  stages/tasks/documents/history, external profiles, provisioning — EXISTS
- Commercial: customers+addresses+contacts+documents, leads+activities,
  inquiries+activities+attachments, cargo assessments, quotations+items+events,
  rate cards, handovers — EXISTS
- Cargo: pre-alerts, receivings, packages(QR ref), events, measurements,
  inspections, repackagings, warehouses, locations, movements, incidents — EXISTS
  (PARTIAL: label print, photo upload path)
- Shipment: shipments+packages, airlines, bookings, AWB(+mod7), manifests,
  handovers, airport dispatches, flights+events, documents, exceptions,
  consolidation/split — EXISTS (PARTIAL: sea/BoL not modelled — air-first)
- Arrival: arrivals, package receipts, discrepancies, events, customs cases+
  charges+events, clearing agents, import docs, airport collections,
  distribution batches+items, release overrides — EXISTS
- Delivery: orders+packages, zones, services, drivers, vehicles, assignments,
  attempts, events, locations, incidents, PODs, returns, collections, COD — EXISTS
  (PARTIAL: live map view, routing provider)
- Portal: dashboard, tracking timeline, statements(read Finance), tickets via
  Ticketing, complaints+events, claims+events(+SoD+settlement ref), feedback,
  messages, comm prefs, privacy requests, C360 — EXISTS (PARTIAL: doc centre,
  SLA wiring to customer tickets, PDF exports)
- Sales: pipeline stages, opportunities, targets+achievement(collected-only),
  segments, acquisitions+milestones, contracts+versions(signature gate), partners
  (agents/brokers)+performance, commission rules+records(dup-safe, collected-
  gated, settle w/ Finance ref), campaigns(validity+usage), pricing approvals
  (SoD), simulate(rate-card precedence+campaign), analytics(pipeline≠invoiced≠
  collected), forecast(labelled) — EXISTS (PARTIAL: tiered bands, e-sign upload)
- Procurement: suppliers+supplier_invoices exist (Finance AP) but
  requisitions/RFQ/PO/GRN/matching/budget-consumption — MISSING
- Notifications: notifications table (Laravel) + customer_messages + comm prefs —
  PARTIAL (no SMS/WhatsApp/email transport beyond sendmail-in-progress)
- Documents: managed_files store — PARTIAL (no entity polymorphism/versioning)
