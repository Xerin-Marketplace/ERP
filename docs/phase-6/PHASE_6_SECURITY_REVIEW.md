# Phase 6 — Security Review

- **Backend enforcement**: `can:` middleware on every web route; company scope taken from `user.employee.company_id` server-side — request payloads never set company_id.
- **Isolation**: every transactional table carries `company_id`; posting validates account.company_id == journal.company_id; API lists filter by resolved company. Cross-company test confirms invisibility.
- **SoD**: journal approve blocks creator; finance_approvals blocks creator; reconciliation approve blocks preparer.
- **Immutability**: no update/delete paths for GL entries; posted journal edits rejected.
- **Sensitive data**: bank account numbers nullable + display-only; no credentials stored; supplier TIN is business data not secret.
- **Audit**: create/submit/approve/reject/post/reverse/period-close/reopen/budget/asset events logged via AuditService with actor+timestamp.
- **Idempotency/concurrency**: unique GL line key, row locks on journal post/reverse and budget consume.
- **Files**: expense receipts reference `managed_files` (existing private storage + auth checks).
