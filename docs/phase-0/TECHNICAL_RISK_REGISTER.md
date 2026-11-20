# Technical Risk Register

| ID | Risk | Severity | Likelihood | Mitigation |
|---|---|---|---|---|
| R-01 | Postgres credentials exposed in chat/commands | **Critical** | done | Rotate DB password on server; update .env; rotate in Contabo/console |
| R-02 | Domain B = 26 missing modules, core business | **Critical** | certain | Start Cargo domain immediately; slice: Receiving→Shipment→Delivery |
| R-03 | Frontend stack undecided (Blade vs Next.js) | **High** | blocking | Management decision before any new UI work |
| R-04 | No CI/CD; manual deploys | High | certain | Add minimal GitHub Actions: tests + Postgres job |
| R-05 | Branch isolation is convention, not enforced layer | High | medium | BranchScope trait + policy tests per new module |
| R-06 | Backup/restore never tested on server | High | unknown | Run restore drill; automate pg_dump cron + offsite copy |
| R-07 | TZ/UAE tax & statutory rules must not be invented | High | — | Await professionally verified rules; engine must be config-driven |
| R-08 | Payroll→GL not linked; risk of double-entry gaps | Medium | certain | Post payroll runs to journals |
| R-09 | Database queue under cargo notification load | Medium | medium | Install Redis, switch QUEUE/CACHE |
| R-10 | modules_statuses.json conflicts on every deploy | Low | certain | Add to deploy runbook: `git checkout -- modules_statuses.json` |
| R-11 | New modules skip scopeFor → data leak | Medium | medium | Module checklist + test requirement (see R-05) |
| R-12 | No API tests | Medium | medium | Add v1 contract tests when endpoints added |
| R-13 | Single-branch user model limits future group staff | Low | future | Multi-branch assignment table when needed |
