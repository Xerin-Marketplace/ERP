# Testing Guide

Runner: PHPUnit via `php artisan test`. Tests use in-memory SQLite
(`phpunit.xml`: `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) — zero setup.

## Run

```bash
php artisan test                    # all
php artisan test tests/Feature/Platform   # foundation suite
php artisan test --filter=ModuleEngineTest
```

## Current coverage (44 tests, all passing)

| Area | File |
|---|---|
| API envelope, token issue/revoke, error format | `Feature/Platform/ApiFoundationTest` |
| Module discovery, registration, module routes | `Feature/Platform/ModuleEngineTest` |
| Settings, audit, navigation registry | `Feature/Platform/SharedServicesTest` |
| Roles/permissions, account status, system page | `Feature/Platform/AuthorizationTest` |
| Breeze auth (login, register, reset, verify) | `Feature/Auth/*` |
| Profile management | `Feature/ProfileTest` |

## Writing module tests

- Put tests in `Modules/<Module>/tests/` mirroring the suite style.
- Use `RefreshDatabase`, factories, `actingAs`, Sanctum `createToken`.
- Every permission-protected route needs a 403 test for unauthorized users.
- Test the envelope, not just the status code (`assertJson(['success'=>...])`).

## CI

Same command works headless. Add GitHub Actions later:
`composer install → php artisan test`.
