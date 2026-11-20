# Architecture Decision Records (summary)

| # | Decision | Status | Rationale |
|---|---|---|---|
| 1 | Modular monolith (nwidart) | CONFIRMED — in use | Proven: 9 modules, clean ownership |
| 2 | Frontend: Blade/Tailwind/Alpine | NEEDS APPROVAL (deviates from Next.js baseline) | 173 working views; rebuild cost >> benefit |
| 3 | Database: PostgreSQL | CONFIRMED (deviates from MySQL baseline) | Deployed, stricter checks caught real bugs |
| 4 | Branch-scoped numbering refs | CONFIRMED | EMP-TZ, IT-TZ, XERIN-PAY — concurrency-safe row-lock |
| 5 | Sensitive-data encryption at rest + masking | CONFIRMED | national_id, bank accounts |
| 6 | Per-module approvals today → central workflow engine | PROPOSED | Cargo will need ~15 approval types; build once |
| 7 | Event-driven module communication | PROPOSED | Cargo lifecycle milestones → notifications |
| 8 | managed_files as universal doc storage | CONFIRMED | Reused by employee docs + ticket attachments |
| 9 | API versioning `/api/v1` | CONFIRMED | 123 routes already versioned |
| 10 | Central BranchScope policy | PROPOSED | Prevent silent leaks in new modules |
| 11 | Redis queue/cache on server | PROPOSED | Before cargo notification volume |
| 12 | Multi-currency AED/TZS service | PROPOSED | exchange_rates table exists; no conversion service |
| 13 | i18n (EN+SW) via lang files | PROPOSED | Required by baseline; not started |
| 14 | Customer master (not finance-owned) | PROPOSED | customers table is AR-scoped today |
| 15 | Cargo = customer-owned property, never company inventory | PROPOSED | Prevents procurement/inventory collision |
| 16 | OpenAPI spec generation | PROPOSED | api.docs page exists, no spec file |
| 17 | Postgres CI test run | PROPOSED | Catch strict-mode bugs before deploy |
| 18 | Payroll → GL journal posting | PROPOSED | payroll_runs exist, no journal link |
