# Security & Technical Debt

## Security findings
| # | Severity | Finding |
|---|---|---|
| 1 | HIGH | No MFA on any account type |
| 2 | MED | provider/DB secrets live in server .env — acceptable but no rotation policy |
| 3 | MED | `commercial_partners.bank_details` plaintext column, no restricted-view enforcement yet |
| 4 | MED | department-level data isolation is convention, not enforced in queries |
| 5 | LOW | rate limiting thin outside public intake; file upload AV-scanning none |
| 6 | LOW | seeded admin password documented in history — rotate on production |

## Tech debt
- `users.delete` seed-order dependency found during audit (fixed) — illustrates
  seeder ordering fragility; consider contract test for full seed.
- In-memory model defaults vs DB defaults caused two test-time bugs
  (status/currency nulls) — fixed by explicit defaults in service code.
- Tiered commission basis falls back to fixed amount (bands unmodelled).
- Portal SLA not wired to ticket_sla_policies.
- Blade duplication in list views (acceptable; component extraction optional).
- No report-builder/export infra (CSV/PDF) — recurring phase-level gap.
