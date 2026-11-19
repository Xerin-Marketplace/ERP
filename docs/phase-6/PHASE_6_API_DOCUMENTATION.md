# Phase 6 — API

Base: `/api/v1/finance` — `auth:sanctum` + `account.active`. Envelope `{success,data}`.

| Method | Path | Purpose |
|---|---|---|
| GET | /dashboard | GL-derived KPIs |
| GET | /accounts | COA (company-scoped) |
| GET/POST | /journals | List / create draft |
| POST | /journals/{id}/submit | Submit for approval |
| POST | /journals/{id}/approve | Approve (self-approval blocked) |
| POST | /journals/{id}/post | Post to GL |
| POST | /journals/{id}/reverse | Controlled reversal |
| GET | /ledger?account_id= | GL with running balance |
| GET | /reports/trial-balance | Trial balance |
| GET | /payables · /receivables | Invoice lists |
| POST | /payables/{id}/post · /receivables/{id}/post | Post invoice to GL |
| POST | /payments | Record + allocate payment |

Full OpenAPI: `docs/api/openapi.yaml` (14 finance paths).
