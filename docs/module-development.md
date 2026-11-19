# Module Development Guide

Engine: `nwidart/laravel-modules` v13. Modules live in `Modules/`.

## Create a module

```bash
php artisan module:make Organization
php artisan module:list        # verify discovery
composer dump-autoload         # if providers aren't autoloading
```

Generated layout (used conventions):

```
Modules/Organization/
├── app/
│   ├── Http/Controllers/
│   ├── Models/
│   └── Providers/OrganizationServiceProvider.php
├── config/config.php
├── database/{migrations,seeders}
├── routes/{web.php,api.php}
└── module.json
```

> Recommended additions per module: `Services/`, `Actions/`, `DTOs/`,
> `Enums/`, `Http/Requests/`, `Http/Resources/`, `Policies/`, `Events/`,
> `Listeners/`, `Jobs/`, `tests/`. Only create what the module needs.

## Naming conventions

- Module: PascalCase singular — `Organization`, `Ticketing`, `Procurement`.
- Tables: `snake_case`, prefixed by module where ambiguity exists — `org_branches`.
- Routes: web under module slug (`/organization/...`), API under `/api/v1/organization/...`.
- Permissions: `<domain>.<action>` — `organization.view`, `branches.manage`.
- Seeders: `<Module>DatabaseSeeder` inside `database/seeders`.

## Registering routes

`routeServiceProvider` (generated) already maps `routes/web.php` + `routes/api.php`.
API routes automatically live under `/api` + module prefix and get the `api.` name prefix.

## Contributing navigation

Do **not** hardcode sidebar items. Register through `NavigationRegistry` in the
module's service provider `boot()`:

```php
use App\Services\Navigation\NavItem;
use App\Services\Navigation\NavigationRegistry;

public function boot(): void
{
    app(NavigationRegistry::class)->register(new NavItem(
        module: 'Organization',
        label: 'Branches',
        icon: 'building',          // Heroicon-style key
        route: 'organization.branches.index',
        permission: 'organization.view',
        group: 'Organization',
        order: 20,
    ));
}
```

Items render only when the current user has the permission.

## Declaring permissions

Each module seeds its own permissions:

```php
Permission::firstOrCreate(['name' => 'branches.manage']);
```

Then protect routes: `->middleware('permission:branches.manage')`.

## Module migrations

```bash
php artisan module:make-migration create_branches_table Organization
php artisan migrate            # runs module migrations too
php artisan module:migrate-rollback Organization
```

## Module tests

Place in `Modules/<Module>/tests/` (Pest/PHPUnit). Run all: `php artisan test`.

## Enable / disable

```bash
php artisan module:disable Organization
php artisan module:enable Organization
```

## Rules

- Modules communicate via shared services (`app/Services`) or Laravel events — never
  by importing another module's classes.
- Reuse `ApiResponse`, `AuditService`, `FileService`, `SettingsService`.
- No placeholder routes or dead controllers — ship only working endpoints.
