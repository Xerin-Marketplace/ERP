# Authentication & Authorization

## Authentication (AuthN)

| Channel | Mechanism |
|---|---|
| Web | Laravel Breeze (session): login, logout, register, password reset, email verify |
| API | Sanctum bearer tokens via `/api/v1/auth/token` |

Security built in: bcrypt hashing, CSRF on forms, throttled login
(`throttle:login`), signed password-reset URLs, session regeneration.

## Account status

`users.status` → `App\Enums\AccountStatus`:

`pending` · `active` · `suspended` · `deactivated`

`EnsureAccountIsActive` middleware (`account.active`) runs on the authenticated
web group and API routes — non-active accounts are logged out / rejected with 403.
Authentication ≠ authorization: a valid login does not imply module access.

## Authorization (AuthZ)

Spatie Laravel Permission v8 (cached).

- **Roles**: `super-admin`, `admin`, `manager`, `user` (seeded).
- **Permissions**: `<domain>.<action>` — `system.view`, `users.view`, `users.manage`,
  `audit.view`, `settings.manage`, `files.manage`.
- **Seeder**: `database/seeders/RolePermissionSeeder.php` — idempotent.

Middleware aliases (registered in `bootstrap/app.php`):

```php
Route::middleware('permission:system.view')->get('/admin/system', ...);
Route::middleware('role:super-admin|admin')->group(...);
```

Policies for resource-level checks; `Gate::before` gives `super-admin` full access.

## Scoped authorization (Phase 1+)

Designed extension points: organizational scope columns on `model_has_roles`
(company/branch/department) and scope-aware permission checks. Do not hardcode
scope logic in Phase 0 — use the middleware/policies provided.

## Auth audit trail

`Login`, `Logout`, `Failed` auth events are written to `audit_logs`
(see `AuditService`). Includes actor, IP, user agent.
