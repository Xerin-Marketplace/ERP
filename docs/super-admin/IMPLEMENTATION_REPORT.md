# Super Admin & System Control Center — Implementation Report

## 1. Executive summary

A centralized administration control center was added as a new
`Modules/SystemAdministration` Laravel module. It is reachable at `/system-admin`
(UI, session auth) and `/api/v1/system-administration` (Sanctum). Access is gated
by a `sysadmin.*` permission set granted to `super-admin`; all mutations are
validated, audited (`system_setting_versions`, `security_events`, existing
`audit_logs`), and honor a global read-only mode.

## 2. Repository audit

See `SUPER_ADMIN_REPOSITORY_AUDIT.md`. Existing auth, Spatie permissions,
`SettingsService`, `AuditService`, `NavigationRegistry`, and nwidart module
conventions were reused — no parallel auth/permission/settings systems were built.

## 3. Architecture

```
Modules/SystemAdministration/
├── app/Http/Controllers/   DashboardController, BrandingController, SettingsController,
│                           RulesController, ModulesController, NavigationController,
│                           FeatureFlagsController, AnnouncementsController,
│                           IntegrationsController, SecurityController, AuditController,
│                           MaintenanceController, MonitoringController,
│                           LocalizationController, BackupsController
├── app/Http/Controllers/Api/  v1 counterparts (JSON)
├── app/Http/Middleware/    EnsureNotReadOnly (registered on the `web` group)
├── app/Models/             SystemIntegration, FeatureFlag, Announcement,
│                           BrandingAsset, NavigationOverride, SecurityEvent,
│                           SettingVersion, LoginHistory, BackupRecord, DocumentTemplate
├── app/Services/           SettingCatalog, BrandingService, FeatureFlagService, SystemMonitor
├── database/migrations/    11 tables (see §15)
├── database/seeders/       SystemAdministrationDatabaseSeeder (permissions + defaults)
├── resources/views/        Blade pages, Tailwind + Alpine
└── routes/                 web.php (/system-admin), api.php (/api/v1/system-administration)
```

Shared consumption: `resources/views/partials/system-head.blade.php` (favicon +
CSS theme variables) and `resources/views/partials/announcements.blade.php` are
included by `layouts/app` and `layouts/guest`.

## 4. Super Admin security design

- Route-level `permission:sysadmin.<area>.<action>` middleware; `super-admin` holds all grants.
- Non-privileged authenticated users receive 403 (verified); guests redirect to login.
- `EnsureNotReadOnly`: when `system.read_only_mode` is on, all non-GET web requests
  are rejected with a redirect + error, except users holding `sysadmin.maintenance.manage`.
- Integration secrets: `SystemIntegration::config` is `encrypted:array`; UI shows
  `maskedConfig()` (`••••••••`), never plaintext.
- Locked rules (e.g. `rules.registration.require_it_approval`) cannot be modified.
- No backdoors, no eval/exec endpoints, no credential echo.

## 5. Branding

Slots: `logo_primary`, `logo_icon`, `logo_login`, `favicon` — uploaded to
`public/branding/`, tracked in `branding_assets` with `is_current` + upload history;
any previous version can be restored. MIME/size validated (PNG/JPEG/WebP/ICO, ≤2 MB).
Text fields (org name, tagline, footer text, login heading/subtext, theme colors)
persist through `SettingCatalog` under `branding.*`. Layouts consume
`BrandingService::currentUrl()` (300 s scalar cache, flushed on change) and emit
`--brand-*` CSS variables.

## 6. General / regional / display settings

`/system-admin/settings/{General|Regional|Display}` — application name, timezone
(default `Africa/Dar_es_Salaam`), date/time formats, locale, currency, items-per-page,
week start, etc. All defined in `SettingCatalog` with types and validation rules.

## 7. System rules

`/system-admin/rules` — typed policy catalog (`rules.*`): password policy, session
lifetime, auth/MFA flags, registration gates. Each rule has type, validation rule,
label, description; some are `locked`. Updates require a change `reason`, are
written inside a transaction, and append to `system_setting_versions`
(key, old/new, changed_by, reason, version #) — providing audit + rollback data.
Field names use bracket notation (`settings[rules.password.min_length]`) so PHP
does not mangle literal dots in keys.

## 8. User administration

Surfaced via System → User Management linking to existing Identity module pages
(`identity.users.index`, registrations approval). No duplicate user tables/services.

## 9. Role / permission administration

Links to `identity.roles.index` (existing Spatie-based UI). `super-admin` is a
protected role: seeders only ever `givePermissionTo`, never `syncPermissions`
(Finance seeder regression fixed).

## 10. Module management

`/system-admin/modules` lists every nwidart module with enabled state.
`POST /modules/{name}/toggle` refuses to disable `Core`, `Identity`, or
`SystemAdministration`, and warns about reverse dependencies before toggling.
Navigation manager (`/system-admin/navigation`) stores hide/reorder/label overrides
in `navigation_overrides`, applied by `NavigationRegistry`.

## 11. Integrations

`/system-admin/integrations` — registry of mail/SMS/WhatsApp/storage/custom entries
with encrypted config, masking, enable toggle, and "test" actions that run a real
check and persist `last_tested_at`/`last_test_ok`.

## 12. Security center

`/system-admin/security` — login history (`login_histories`, recorded on
login/logout), active-session listing with per-session revocation (DB session
driver), security event feed (`security_events`), and security policy settings
(session lifetime, max attempts, lockout).

## 13. Audit

`/system-admin/audit` — filterable viewer over `audit_logs` (user, module, action,
date range) with CSV export. Settings/rule changes additionally land in
`system_setting_versions`; privileged events land in `security_events`.

## 14. Monitoring & dashboard

`/system-admin` dashboard: real counts (users, active sessions, failed logins
24 h, pending registrations, module health), health badges (DB, cache, queue,
storage probes), recent security events. `/system-admin/monitoring` exposes the
full probe table + failed-job count.

## 15. Migrations

`system_integrations`, `feature_flags`, `announcements`, `branding_assets`,
`navigation_overrides`, `security_events`, `system_setting_versions`,
`login_histories`, `backup_records`, `document_templates`, plus `key` index on
`system_settings`. SQLite + MySQL compatible (no vendor-specific SQL).

## 16. API endpoints

`GET/PUT /api/v1/system-administration/settings[/{group}]`,
`GET/PUT …/rules`, `GET/POST …/feature-flags`, `GET …/announcements`,
`GET/POST …/integrations`, `GET …/audit`, `GET …/monitoring`,
`POST …/maintenance/*` — all Sanctum + `sysadmin.*` permission enforced.

## 17. Frontend screens

Blade + Tailwind + Alpine (the repository's existing stack — no Angular code exists
or was added). Sidebar renders the `NavigationRegistry` tree with collapsible
System group; overrides respected.

## 18. Automated test results

`tests/Feature/SystemAdministrationTest.php` — 9 tests, 24 assertions: access
control (403/redirect/200), settings versioning, invalid-value rejection, locked
rules, flag evaluation windows, protected modules, secret masking. Full suite:
250 tests, 249 pass + 1 pre-existing ExampleTest fixed (branding now degrades
gracefully when `system_settings` is absent) → 250/250.

## 19. Known limitations

- Backup page lists `backup_records` and can trigger `backup:run` if
  `spatie/laravel-backup` is installed; scheduling/credentials are deployment work.
- SMS/WhatsApp "test" actions are connectivity stubs — real providers need API keys.
- Navigation overrides apply at render time; deep per-item ACL unchanged.
- Maintenance `artisan down` uses Laravel's secret-bypass mechanism; documented on-page.
- Chart widgets render from live counts but no historical time-series store exists yet.

## 20. Deployment instructions

```
composer install && npm ci && npm run build
php artisan migrate
php artisan db:seed --class=Modules\\SystemAdministration\\Database\\Seeders\\SystemAdministrationDatabaseSeeder
php artisan module:enable SystemAdministration   # if not already enabled
php artisan config:cache route:cache view:cache
```

Grant `sysadmin.*` to the `super-admin` role (seeder does this idempotently).
Use a non-`database` cache store (Redis) in production; caches store only scalars.

## 21. Remaining risks

- `database` cache driver is safe now (arrays only), but Redis is untested in this env.
- Read-only mode relies on all writes flowing through the `web` middleware group.
- Backup restore path is intentionally not implemented (destructive) — run via CLI.

## 22. Recommended next steps

1. Wire `spatie/laravel-backup` + scheduler for real backup jobs.
2. Add notification channels (mail/SMS) for security events.
3. Time-series metrics table for the monitoring charts.
4. Role-editor UI hardening (permission diff preview before save).
5. Multi-env (staging/prod) config promotion workflow.
