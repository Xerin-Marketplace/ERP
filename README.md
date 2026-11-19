# XERIN GROUP ERP

**Xerin Group Company Limited** — enterprise ERP platform.
Architecture: Laravel 13 **modular monolith** · `nwidart/laravel-modules` ·
Blade + Alpine + Tailwind · Sanctum API · Spatie RBAC.

## Status

**Phase 0 — Enterprise Core Foundation: complete.**
See [docs/phase-0-completion-report.md](docs/phase-0-completion-report.md).

## Stack

| Layer | Choice |
|---|---|
| Framework | Laravel 13.43, PHP 8.4 |
| Modules | nwidart/laravel-modules 13.3 |
| Web auth | Laravel Breeze (Blade) |
| API auth | Laravel Sanctum (`/api/v1`) |
| RBAC | Spatie Laravel Permission |
| DB | MySQL/MariaDB (prod) · SQLite (dev) |
| Cache/Queue | Redis (prod) |
| UI | Blade + Alpine + Tailwind (sidebar-08 style) |

## Quick start

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm run build && php artisan serve
```

Verify: `php artisan test` (44 tests) · `php artisan module:list` ·
`curl localhost:8000/api/v1/health` · API docs at `/api-docs`.

## Documentation

| Doc | Content |
|---|---|
| [docs/architecture.md](docs/architecture.md) | System design & decisions |
| [docs/installation.md](docs/installation.md) | Setup & env reference |
| [docs/module-development.md](docs/module-development.md) | Create/register modules |
| [docs/api-standards.md](docs/api-standards.md) | API conventions & envelope |
| [docs/api/openapi.yaml](docs/api/openapi.yaml) | OpenAPI 3 spec |
| [docs/auth.md](docs/auth.md) | AuthN/AuthZ, roles, account status |
| [docs/shared-services.md](docs/shared-services.md) | Settings, audit, files, nav, notifications |
| [docs/design-system.md](docs/design-system.md) | UI tokens & components |
| [docs/database.md](docs/database.md) | Conventions & Phase 0 tables |
| [docs/testing.md](docs/testing.md) | Test guide & coverage |
| [docs/security.md](docs/security.md) | Security baseline |
| [docs/backup-recovery.md](docs/backup-recovery.md) | Backup & restore |
| [deploy.md](deploy.md) | Production VPS deployment |

## Phase 1

Organization & Branch Management — built on this foundation.
