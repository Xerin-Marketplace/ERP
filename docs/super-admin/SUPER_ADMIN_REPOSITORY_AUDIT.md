# Super Admin — Repository Audit

Audit of the existing XERIN GROUP ERP repository performed before implementing the
System Administration control center. Purpose: extend existing infrastructure rather
than rebuild it.

## 1. Existing administration capabilities

| Area | Pre-existing capability |
|---|---|
| Auth | Laravel session auth + Sanctum for API. Login throttle, password reset, email verification. |
| Roles/permissions | Spatie `laravel-permission`. `RolePermissionSeeder` seeds roles (`super-admin`, `admin`, `hr-manager`, `auditor`, `employee`, …) and ~160 module permissions. |
| User management | Identity module: user CRUD, registration approval workflow, role assignment (`identity.users.*`, `identity.roles.*`, `identity.registrations.*`). |
| Audit | `AuditLog` model + `AuditService`; organization audit viewer at `organization.audit`. |
| System overview | `admin.system` page (`Admin\SystemController`) with basic stats. |
| Settings | `SystemSetting` model (key/value/group/type/is_encrypted) + `SettingsService` (cache, casting, `Crypt` encryption). One legacy UI at `admin.settings`. |
| Navigation | `NavigationRegistry` + `NavItem` service; providers register permission-checked items; rendered in `layouts/app.blade.php`. |
| API | v1 routes under `/api/v1/*`, Sanctum-protected; OpenAPI docs at `/api-docs`. |

## 2. Existing settings infrastructure

`system_settings` table: `key` (unique), `value`, `group`, `type`, `is_encrypted`,
`updated_by`. `SettingsService` provides typed reads (`string|bool|int|float|json`),
`rememberForever` caching with per-key invalidation, and encrypted storage via
`Crypt::encryptString`.

Gaps found: no schema/validation per key, no change history, no ownership metadata,
no default catalog — a wrong value could be persisted silently and no rollback was
possible.

## 3. Identity / user administration services

- `User` model: `status`, `last_login_at`, Spatie `HasRoles`.
- Registration requests table + approval flow with IT-approval gate.
- Password policy: Laravel defaults only; no central policy object.

## 4. Module registration mechanism

`nwidart/laravel-modules`. Each module: `module.json`, `app/Providers/*ServiceProvider.php`,
`routes/{web,api}.php` loaded by the provider with `web`/`api` + prefix groups,
`database/{migrations,seeders}`. Modules enabled via `modules_statuses.json`.
`Module::find($name)->isEnabled()|enable()|disable()` for runtime control.

Risk found: nothing prevented disabling a module that others depend on, and one
seeder (`FinanceDatabaseSeeder`) called `syncPermissions()` on `super-admin`,
wiping all non-finance grants — fixed to `givePermissionTo`.

## 5. Configuration duplication risks

- Two settings UIs (legacy `admin.settings` vs new center) → resolved: legacy route
  now 301-redirects to `/system-admin/settings/General`.
- Identity nav items were registered both standalone and under the System group →
  standalone registration removed; System children link to the same routes.
- `config/app.php` timezone/name vs DB settings → DB is now the source of truth
  surfaced via `SettingCatalog`; `config/app.php` remains the boot-time fallback.

## 6. Security gaps found

| Gap | Resolution |
|---|---|
| No read-only/maintenance enforcement for mutations | `EnsureNotReadOnly` middleware on `web` group; `sysadmin.maintenance.manage` bypass. |
| No privileged-area permission layer beyond `super-admin` role | `sysadmin.*` permission set, enforced via `permission:` middleware per route. |
| Secrets could be stored plaintext | `SystemIntegration` encrypts `config` JSON at rest; `maskedConfig()` for display. |
| Settings had no audit trail | `system_setting_versions` records old/new value, actor, reason. |
| Cache of Eloquent objects broke under `database` cache driver | All shared caches now store scalars/arrays only. |
| Unconditional sidebar rendering | Nav tree filters on `can(permission)` recursively. |

## 7. Required extensions (implemented)

- `Modules/SystemAdministration` with domain services: `SettingCatalog`,
  `BrandingService`, `FeatureFlagService`, `SystemMonitor`, `NavigationOverride`,
  `SecurityEvent`, `BackupRecord`, `LoginHistory`.
- Versioned, schema-validated settings (`SettingCatalog::updateMany`).
- Nested navigation (`NavItem::children`) with permission-filtered rendering.
- Read-only middleware + maintenance controls.
- API surface `/api/v1/system-administration/*` (Sanctum + `sysadmin.*`).

## 8. Integration architecture

`system_integrations` registry: key, name, driver (`mail|sms|whatsapp|storage|custom`),
encrypted JSON config, `last_tested_at`/`last_test_ok`. Test actions dispatch a
driver-specific check (mail → `Mail::raw` to configured address; others →
connectivity stub) and record the result — never fabricate success.

## 9. Implementation sequence (as executed)

1. Audit + fix seeders/nav foundations.
2. Module scaffold, migrations, models, seeder + `sysadmin.*` permissions.
3. `SettingCatalog` + rules/versioning; controllers & Blade views.
4. Branding (uploads, history, restore) + layout integration.
5. Flags, announcements, integrations, security center, audit viewer.
6. Module manager + navigation overrides; maintenance/monitoring/backups/localization.
7. API v1, middleware, tests, docs, verification.
