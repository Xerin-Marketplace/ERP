# Identity Module — Technical Documentation

**Module:** `Modules/Identity` · **Phase:** 2 · **Security-critical**

Central rule: **no person receives internal ERP access by registering.**
`POST /register` creates a `registration_applications` row — nothing else.
An account exists only after IT approves, assigns and provisions it, and
becomes usable only after the applicant activates via a secure invitation.

## 1. Data model

| Table | Purpose |
|---|---|
| `registration_applications` | Applicant data + requested (non-authoritative) placement + workflow status. `reference` = `XERIN-REG-YYYY-000001` (race-safe, locked sequence). Soft-deleted. |
| `registration_documents` | Links applications to `managed_files` (private disk, FileService-validated). |
| `registration_status_histories` | Append-only `from → to` transitions with actor + note. `updated_at` disabled. |
| `registration_reviews` | Internal IT notes — **never** exposed to applicants. |
| `user_profiles` | Extended identity: employee_number (unique), job title, DOB, photo, addresses. |
| `employee_assignments` | Append-only placement history (company/branch/dept/position/manager + effective dates + type). Current row = `status active|temporary AND effective_end IS NULL`. |
| `access_invitations` | SHA-256 hashed tokens, 72h TTL, single-use, revocable. |
| `login_histories` | login/logout/failed with IP + UA (event subscriber). |
| `account_security_events` | suspensions, revocations, activations, lockouts. |

## 2. Workflow (`ApplicationStatus`)

`draft → submitted → pending_review → under_review → information_requested |
approved → provisioning → active` (+ `rejected`, `cancelled`).
`canTransitionTo()` map enforces legal moves inside `DB::transaction` +
`lockForUpdate`. Every move writes a history row + audit entry.

Reject requires a reason; info-request requires an applicant-visible message
(stored separately from internal notes).

## 3. Security design

- **Applicant tracking**: reference **and** email required; 8-attempt/5-min
  rate limit per IP; timeline shows public labels only.
- **Provisioning** (`ProvisioningService`): one transaction creates user
  (status `pending`, random unknown password), `user_profile`,
  `employee_assignment`, role and invitation. `super-admin` is blocked.
  Employee numbers auto-generate `XER-#####`.
- **Activation**: `/activate/{token}` — token resolved by hash, expiry and
  single-use enforced with row locking; sets password → status `active` →
  application → `active`.
- **Lifecycle** (`UserLifecycleService`): suspend/deactivate revoke Sanctum
  tokens, DB sessions and open invitations; writes security events + audit.
- **Ownership checks**: `AssignmentService::validatePlacement` verifies
  branch∈company, department∈company, branch-scoped dept compat, all `active`.
- **Protected roles** (`IdentityDatabaseSeeder::PROTECTED_ROLES`):
  non-editable, non-deletable, non-assignable via UI/provisioning.
- **Self-protection**: users can't suspend/deactivate/role-change themselves.

## 4. Roles seeded (9 new)

`it-administrator` (all identity perms), `it-officer` (review+approve+reject),
`group-administrator`, `company-administrator`, `branch-manager`,
`department-manager`, `employee` (no admin perms), `auditor`,
`management-viewer`. Protected: `super-admin`. 26 new permissions.

## 5. Surfaces

- Public: `/register` (application form), `/register/track`, `/activate/{token}`,
  `POST /api/v1/identity/register`, `GET .../registration/status/{ref}`
- IT: `/identity/registrations` (stats + review), `/identity/users` (lifecycle),
  `/identity/roles` (permission matrix), `/identity/me`
- API: `/api/v1/identity/me`, `/admin/registrations/*`, `/admin/users/*`,
  `/admin/roles` — all permission-gated.
- Dashboard: IT users see the registration queue; provisioned users see
  "My placement".

## 6. Notifications

`AccountActivationInvitation` (mail+database, queued), `IdentityNotification`
(mail+database) for approve/reject/info-request — sent on-demand to the
applicant email. SMS/WhatsApp extendable via `via()`.

## 7. Breaking change vs Phase 0/1

`/register` no longer creates a `User`. `RegisteredUserController` deleted;
`RegistrationTest` rewritten for the application model. Admin seeding still
creates the initial super-admin directly.

## 8. Tests

`Modules/Identity/tests/Feature/IdentityModuleTest.php` — 33 tests covering
transitions, permissions, provisioning, activation tokens (expired/reuse/
revoke), lifecycle revocation, assignment history, API envelope, note secrecy,
protected roles. Full suite: 119 tests.
