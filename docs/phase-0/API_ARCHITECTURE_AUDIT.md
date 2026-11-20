# API Architecture Audit

- **123 routes under `/api/v1`** — versioning discipline exists.
- Coverage: auth, core, employees + employee reference data (positions, grades, document categories). HR pillars (compensation/payroll/bank), Ticketing, Finance, Reception have **web-only** routes today.
- Sanctum token auth; `personal_access_tokens` table present.

## Conventions observed
- JSON resources, paginated lists, consistent 403/422 handling (matches web layer).

## Gaps vs. enterprise needs
- No OpenAPI/Swagger spec file (api.docs page exists but isn't generated spec).
- No rate-limiting config beyond defaults; no correlation IDs.
- New HR pillars + Ticketing need v1 endpoints before any external/mobile integration.
- No idempotency keys — required before webhook/payment integrations.
