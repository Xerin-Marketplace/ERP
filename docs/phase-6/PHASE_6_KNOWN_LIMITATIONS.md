# Phase 6 — Known Limitations

- No PDF/Excel export yet — reports render in-browser; export layer deferred (same as Phase 5).
- Bank statement import accepts structured rows (CSV adapter UI not built); **no direct bank connectivity** is claimed.
- Payment recording ≠ money movement; no gateway integration.
- Recurring invoices, credit-note journals, proforma flows exist in schema/status but have minimal UI.
- Budget `approval` policy returns `requires_approval` — caller-side workflow not yet wired.
- Consolidation (intercompany eliminations, FX translation) is intentionally out of scope; company consolidation-readiness only.
- Angular frontend: repo uses Blade/Alpine; finance screens follow that existing system per repository reality.
- Payroll integration: readiness only (verified overtime records from HR are the hand-off point).
