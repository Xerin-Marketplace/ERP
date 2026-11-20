# SA.12 FINAL HARDENING — IMPLEMENTATION REPORT

Baseline: SystemAdministration built in e48bbce extended — NOT rebuilt.

## SA.12.1 Scoped settings — IMPLEMENTED+VERIFIED
- `system_setting_scopes` (key+scope_type+scope_id unique)
- `ScopedSettingsResolver`: precedence user→branch→company→group→global→default;
  `allowed_scopes` + `protected` def metadata; branch/company/user scope access
  enforced server-side; cast by type; cache per candidate set
- `system.locale` registered with all scopes; `rules.auth.*` marked protected
- Verified: company override doesn't leak; user pref wins; protected rejects
  scope writes; unsupported scope rejected

## SA.12.2 Approval governance — IMPLEMENTED+VERIFIED
- `system_setting_change_requests`: low/medium auto-publish; high/critical
  → pending; self-approval blocked; base_version optimistic lock → 'stale'
  on drift; publish revalidates + writes SettingVersion + audit
- Verified: pending queue, self-approve block, 2nd approver publishes +
  versioned, stale rejection, low-risk immediate

## SA.12.3 Real integration testing — IMPLEMENTED+VERIFIED
- `IntegrationTester`: real probes — SMTP EHLO handshake via fsockopen,
  HTTP HEAD for endpoints; statuses: successful|auth_failed|unavailable|
  timeout|config_invalid; results persisted to `integration_test_results`;
  `last_status`/`last_error` updated on the integration
- Verified: dead SMTP host → NOT successful; empty config → config_invalid

## SA.12.4 Platform MFA — IMPLEMENTED+VERIFIED
- `user_mfa` (encrypted secret cast, hashed recovery codes)
- `MfaService`: RFC 6238 TOTP (HMAC-SHA1, 30s, ±1 window) implemented
  in-repo; enroll→confirm activates only after valid code; recovery codes
  single-use; super-admin/privileged tiers ALWAYS required (no scope
  can weaken); `security.mfa.required` setting for org-wide policy
- Verified: enroll→verify→challenge, bad code rejected pre-activation,
  recovery code single-use, privileged always-required
- Deferred: login-flow middleware wiring (challenge step into the web
  login), WebAuthn — tracked below

## Routes
`POST /system-admin/governance/settings/{key}/scope`,
`GET .../settings/{key}/effective`, changes CRUD + approve/reject,
`POST .../integrations/{id}/test`, `.../history`,
`mfa/enroll|confirm|challenge` — all permission-gated

## Tests: 13/13 new SA12 tests pass; sysadmin module suite green
## Known limitations: MFA challenge not yet wired into login middleware;
no MFA reset-request workflow; scheduled health checks not wired;
UI screens for approvals/scope are API-first (Blade pending)
