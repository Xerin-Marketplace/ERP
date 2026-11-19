# System Architecture

**XERIN GROUP ERP — Phase 0 Foundation**
Organization: Xerin Group Company Limited

## Overview

Laravel **modular monolith**. One deployable application, internally partitioned into
independent business modules via `nwidart/laravel-modules`. No microservices.

```
┌───────────────────────────────────────────────────────┐
│  Presentation: Blade + Alpine + Tailwind (ERP shell)  │
├───────────────────────────────────────────────────────┤
│  HTTP layer: routes/web.php · routes/api.php ·        │
│  Modules/*/routes/*.php                               │
├───────────────────────────────────────────────────────┤
│  Module engine (nwidart)                              │
│   ├── Modules/Core        — platform status, nav      │
│   └── Modules/<Business>  — future (Org, HR, ...)     │
├───────────────────────────────────────────────────────┤
│  Shared core (app/)                                   │
│   ├── Support/ApiResponse      — uniform envelope     │
│   ├── Services/SettingsService — system settings      │
│   ├── Services/AuditService    — audit trail          │
│   ├── Services/FileService     — secure uploads       │
│   ├── Services/Navigation      — navigation registry  │
│   └── Enums, Middleware, Models (SystemSetting,       │
│       AuditLog, ManagedFile, User)                    │
├───────────────────────────────────────────────────────┤
│  AuthN/AuthZ: Breeze (web) + Sanctum (API) +          │
│  Spatie Permission (roles/permissions/policies)       │
├───────────────────────────────────────────────────────┤
│  Infra: MySQL/MariaDB · Redis (cache+queue) ·         │
│  Laravel jobs/events/notifications/filesystem         │
└───────────────────────────────────────────────────────┘
```

## Key decisions

| Decision | Choice | Why |
|---|---|---|
| Module engine | `nwidart/laravel-modules` v13 | Industry standard, Laravel 13 support |
| API auth | Sanctum tokens | First-party, SPA/mobile ready |
| RBAC | Spatie Laravel Permission v8 | Roles, permissions, middleware, caching |
| API surface | `/api/v1/*` | Versioned from day one |
| Response format | `{success, message, data}` | Enforced by `ApiResponse` + exception handler |
| Settings | DB-backed `system_settings` | Runtime-editable, grouped, typed, encryptable |
| Audit | Append-only `audit_logs` | Actor, action, resource, old/new values, IP, UA |
| Primary keys | BigIncrements now; ULID allowed per-module | Convention documented in database guide |

## Layering rules

- Controllers are thin — delegate to services/actions.
- Validation lives in FormRequests (module `Http/Requests`).
- Authorization enforced server-side via `permission` middleware + policies.
- Modules never reach into another module's internals — only through `app/` shared services or published events.
- `Modules/Core` is the platform module: status endpoint, navigation contributions, shared service wiring.

## Request lifecycle (API)

Request → `api` middleware group → `auth:sanctum` → `account.active` →
`permission:*` → controller → service → `ApiResponse` envelope.
Exceptions → central handler in `bootstrap/app.php` → consistent JSON errors.
