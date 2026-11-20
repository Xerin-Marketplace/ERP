# Reception & Access Management — Implementation Completion Report

## Executive summary
Access Management layer added inside the existing `Reception` module: public request intake → reception verification → configurable approval workflows → delegated provisioning. No duplicate users, roles, departments, auth, or approval engines — everything integrates with Identity (RoleAssignmentGuard, InvitationService), Organization, Employee, Spatie RBAC, and AuditService.

## Backend
- 10 tables (one migration `2026_12_04_100000_create_access_management_tables`): workflows, versions, stages, requests, documents, verifications, approval tasks, history, external profiles.
- 9 models (`Modules\Reception\Models`), 6 services:
  - `AccessRequestService` — submission, XER-ACC-YYYY-###### refs (lock-safe), tracking tokens, status machine, history.
  - `AccessWorkflowService` — resolution (most-specific type+branch+dept), stage task emission, advancement, versioning (in-flight requests pinned).
  - `ApprovalTaskService` — inbox by role, decide/delegate, **self-approval blocked** (applicant's linked user can't decide own request).
  - `VerificationService` — per-type checklist, gate before routing.
  - `AccessProvisioningService` — idempotent; new→pending user+invite, existing→mutates linked account (never duplicates), external→time-boxed `ExternalUserProfile`+sponsor+expiry. Roles via `syncRoles` only after `RoleAssignmentGuard::assertCanAssign` per role.
  - `AccessDashboardService` — real KPIs.

## Frontend (Blade/Alpine — repo convention, not Next.js)
- Public: `access-request/` landing, typed form, submitted confirmation (ref+token), tracking page.
- Internal: access dashboard, request list/detail (verification checklist, tasks, history timeline, actions), approval inbox, provisioning queue+configure (roles limited to admin's delegation scope), workflow admin.

## Security
- "Reception is not an administrator": `reception.access.review/verify/route` granted to `receptionist`; `reception.access.provision` + `workflows.manage` only to it/group administrators.
- Public tracking requires reference **and** token; throttled 30/min.
- Every decision/decisioner recorded in `access_request_history` + AuditService.

## Tests — actual results
`php artisan test Modules/Reception/tests/Feature/AccessManagementTest.php` — **17 tests, 46 assertions, all pass**.
Covers: submission/refs/tokens, duplicate blocking, external sponsor+expiry requirement, workflow resolution/routing, 3-stage approval completion, unauthorized-decide 403, rejection termination, self-approval block, new-employee provisioning (pending+invite), external time-boxed profile, unapproved-provision block, existing-employee no-duplication, tracking security, portal routes, permission gates, receptionist-cannot-provision, API submit+track.

## API endpoints (OpenAPI updated)
`POST/GET /api/v1/access-requests`, `GET /access-requests/track`, `/{id}`, `/route`, `/reject`, `/provision`, `POST /access-approval-tasks/{id}/decide`.

## Known limitations
- Web form doesn't upload documents yet (document table + relation ready).
- Expiry enforcement job (auto-suspend expired external accounts) not scheduled yet — profile `expires_on` is stored.
- Public form is English-first; Swahili labels pending.
- Workflow conditions support equality matching; complex expressions future.

## Acceptance matrix
| Criterion | Status | Evidence |
|---|---|---|
| Public intake + tracking | PASS | portal tests + track token tests |
| Duplicate detection | PASS | `test_duplicate_active_submission_is_blocked` |
| Configurable workflows by type/branch/dept | PASS | resolution test + workflow admin UI |
| Multi-stage approvals, reject, changes | PASS | 3-stage completion + rejection tests |
| Self-approval prevention | PASS | `test_applicant_cannot_approve_own_request` |
| Delegation-scoped role assignment | PASS | `assertCanAssign` per role in provisioning |
| Provisioning without duplicates | PASS | existing-employee test asserts same user_id |
| External time-limited access | PASS | ExternalUserProfile expiry test |
| Reception cannot provision | PASS | route 403 test |
| Append-only history + audit | PASS | history records + AuditService calls |
| Invitation on provisioning | PASS | `AccountActivationInvitation` asserted |
| API under /api/v1 | PASS | API submit/track tests |
| English + Swahili | PARTIAL | locale infra exists; views English-first |
| Scheduled expiry enforcement | FAIL | not yet implemented |
| Document upload UI | PARTIAL | schema ready, UI not wired |
