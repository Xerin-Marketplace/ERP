# Reception & Access Management — Repository Audit

## Stack (verified)
- Laravel 13.43, PHP 8.4, nwidart/laravel-modules, Spatie Permission, Sanctum.
- Frontend: **Blade + Alpine.js + Tailwind + Vite** — the request brief mentioned Next.js/React; no React frontend exists in this repo. Implementation followed actual repo conventions.
- MySQL/MariaDB target; SQLite for tests.

## Existing components reused (no duplication)
| Area | Component |
|---|---|
| Auth/RBAC | `account.active` middleware, `can:*` gates, Spatie roles/permissions |
| Delegation | `Modules\Identity\Services\RoleAssignmentGuard` — every role assign validated via `assertCanAssign` |
| Invitations | `Modules\Identity\Services\InvitationService` + `AccountActivationInvitation` notification |
| Audit | `App\Services\AuditService` |
| Organization | `Company`, `Branch`, `Department`, `Employee` models — no new copies |
| Navigation | `NavigationRegistry`/`NavItem` |
| Reception | Existing module extended (front-desk ops untouched) |

## Pre-implementation state
- `Modules/Reception` already handled tickets/attendance/directory — Access Management added as bounded domain.
- User's uncommitted `Modules/Sales` WIP and Employee nav/lifecycle changes preserved; stale bootstrap cache issue resolved without touching that work.
