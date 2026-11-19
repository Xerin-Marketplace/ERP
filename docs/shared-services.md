# Shared Enterprise Services

All in `app/Services` — injected via container, usable by every module.

## SettingsService

DB-backed runtime config (`system_settings` table: key, value, type, group, encrypted).

```php
$settings = app(SettingsService::class);
$settings->set('company.name', 'Xerin Group', 'branding');
$settings->get('company.name');
$settings->group('branding');
$settings->set('smtp.pass', 'x', 'mail', 'string', encrypt: true); // encrypted at rest
```

Types: `string`, `int`, `float`, `bool`, `json`. Never store secrets unencrypted —
env vars remain the place for infrastructure secrets.

## AuditService

```php
app(AuditService::class)->log(
    action: 'branch.updated',
    resource: $branch,             // optional model
    old: $branch->getOriginal(),   // optional
    new: $branch->getChanges(),    // optional
    metadata: ['reason' => 'relocation'],
);
```

`audit_logs` captures: user_id, action, resource_type/id, old/new values (JSON),
ip_address, user_agent, metadata, timestamps. Table is append-only — no update/destroy
routes exist by design.

## FileService

```php
$file = app(FileService::class)->store($request->file('doc'), $user, 'contracts');
// validates extension + size (ERP_MAX_UPLOAD_MB), stores on configured disk,
// records ManagedFile metadata (name, mime, size, disk, path, uploader).
$file->temporaryUrl();  // or ->download() — always check authorization first
```

Blocked types: executables/scripts. Download/visibility is enforced by the caller
via permission/policy — files are never public by default.

## NavigationRegistry

Collects `NavItem`s contributed by modules at boot; the sidebar renders
`visible()` items filtered by the current user's permissions.

## Notifications

Laravel notifications table migrated — `database` channel for in-app,
`mail` for email. Prefer notification classes over ad-hoc mail.
Extension points for SMS/WhatsApp adapters land in Phase 1+ (channel classes).

## Queues & cache

`QUEUE_CONNECTION=redis`, `CACHE_STORE=redis` in production (`.env.example`).
Use `ShouldQueue` for slow work; failed jobs land in `failed_jobs`
(`php artisan queue:failed`). Cache convention: prefix keys `erp:<domain>:<key>`,
tag where feasible, invalidate on writes.
