# SUPER ADMIN — REPOSITORY AUDIT (evidence-based)

## Already implemented (verified — do NOT rebuild)
| Area | Evidence |
|---|---|
| Typed settings engine | `system_settings` + `SettingsService` + `SettingCatalog` (schema-validated, risk-classified low→high) |
| Versioning | `system_setting_versions` via `SettingVersion` — every write records old/new, actor, risk, reason |
| Branding | `BrandingController` + `BrandingService` + `system_branding_assets` (uploads, mime/size validation, current flag) |
| Rules | `RulesController` + `rules.*` catalog group (auth/password policies) |
| Modules | `ModuleManagerController` — enable/disable via nwidart, PROTECTED list, audited, data untouched |
| Navigation | `NavigationOverride` + `NavigationRegistry` — visibility never bypasses perms |
| Feature flags | `FeatureFlagService` + `system_feature_flags` |
| Announcements | `system_announcements` |
| Integrations | `SystemIntegration` (encrypted creds via is_encrypted setting support) |
| Monitoring | `HealthService` + `MonitoringController` (DB, queue, storage) |
| Audit center | `AuditCenterController` on append-only `audit_logs` |
| Localization/Maintenance/Backup/Security | controllers + views exist |

## What this change added
**SettingRegistry** (`Modules/SystemAdministration/Services/SettingRegistry`) —
module self-registration. Any module calls `SettingRegistry::register()`
in its provider; keys must be `{module}.`-prefixed; definitions flow through
the same validation/versioning/audit. `SettingCatalog::definitions()` now
merges `coreDefinitions()` + registry. New modules no longer require
editing SystemAdministration.

## Gaps remaining (accepted scope)
- Scope precedence (user → branch → company → global) — not yet wired
- High-risk change approval workflow (risk field + versioning exist; approval queue pending)
- Real provider connection tests for integrations
- MFA enforcement (prompt asks; not implemented platform-wide)
