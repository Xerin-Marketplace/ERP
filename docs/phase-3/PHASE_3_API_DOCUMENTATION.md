# Phase 3 API Documentation

Web routes live under `/employee` (auth + verified + account.active); API v1 under `/api/v1/employees` (Sanctum).

## New in Phase 3

| Method | Route | Permission | Purpose |
|---|---|---|---|
| POST | `/employee/{employee}/status` | `employee.suspend` / `employee.terminate` / `employee.approve` / `employee.update` (per target status) | Auditable lifecycle transition |

**Request body**

```json
{ "status": "suspended", "reason": "Disciplinary hold", "effective_at": "2026-03-01" }
```

`status` ∈ `active, on_probation, on_leave, suspended, resigned, terminated, separated, pending_approval`. `reason` required. `effective_at` optional (defaults to now). Response: redirect with flash; errors → validation.

**Effect:** status updated, `audit_logs` row `employee.status_changed` (old/new/reason/effective_at/actor), `employee_events` entry on the profile timeline.

## Changed behaviour

| Endpoint | Change |
|---|---|
| `POST /employee` | `employee_number` no longer accepted from input; backend generates `EMP-{branch}-{seq}` |
| `PUT /employee/{e}` | `status` removed from editable fields — use the status endpoint |

## Existing (unchanged contract)

- CRUD: `GET/POST /employee`, `GET /employee/{e}`, `GET /employee/{e}/edit`, `PUT /employee/{e}`
- Dashboard `GET /employee/dashboard`, export `GET /employee/export`, self-service `GET/PUT /employee/me`
- Documents `GET/POST /employee/{e}/documents`, `GET /employee/documents/{doc}/download`, `DELETE /employee/documents/{doc}`
- Structure `GET /employee/structure/positions`, `POST .../positions`, `PUT .../positions/{p}`, `POST/PUT .../grades`
- Transfers `GET /employee/transfers`, `POST /employee/{e}/transfers`, `POST /employee/transfers/{t}/approve|reject`
- Changes `GET /employee/changes`, `POST /employee/{e}/changes`, `POST /employee/changes/{c}/approve|reject`
- Lifecycle `GET /employee/onboarding`, `POST /employee/{e}/onboarding`, `POST /employee/onboarding/tasks/{t}`, `GET /employee/offboarding`, `POST /employee/{e}/offboarding`, `POST /employee/offboarding/{r}/approve|complete`
- API v1: `Api/V1/EmployeeApiController` + employee positions endpoints
