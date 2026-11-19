# API Standards

All module APIs must follow these conventions.

## Base & versioning

- Base: `/api/v1/`
- New breaking versions go to `/api/v2/`; v1 stays stable.

## Authentication

`Authorization: Bearer <token>` — issue via `POST /api/v1/auth/token`.
Guard: `auth:sanctum`. Revoke via `POST /api/v1/auth/revoke`.

## Response envelope

Every response uses the shared envelope (`App\Support\ApiResponse`):

```json
{ "success": true, "message": "…", "data": { … } }
```

Errors:

```json
{ "success": false, "message": "Validation failed.", "errors": { "email": ["required"] } }
```

| Status | Meaning |
|---|---|
| 200 | Success |
| 201 | Created |
| 401 | Unauthenticated |
| 403 | Forbidden / account suspended |
| 404 | Not found |
| 422 | Validation failed |
| 429 | Rate limited |

Never return bare exceptions or framework HTML errors — the central exception
handler in `bootstrap/app.php` converts them automatically. Throw normal
Laravel exceptions (`ValidationException`, `ModelNotFoundException`,
`AuthorizationException`) and let the handler format them.

## Pagination / filtering / sorting

- Pagination: Laravel paginator — `?page=2&per_page=25` (max 100).
- Filtering: `?filter[field]=value`.
- Sorting: `?sort=-created_at,name` (`-` = desc).

## Rate limiting

`throttle:api` on all `/api/*` routes. Default `ERP_API_RATE_LIMIT=60` req/min.

## Documentation

Spec: `docs/api/openapi.yaml` — rendered at `/api-docs` (Redoc).
Update the spec in the same PR as any endpoint change.
