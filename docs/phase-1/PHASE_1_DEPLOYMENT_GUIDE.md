# Phase 1 — Deployment Guide

```sh
php artisan migrate
php artisan db:seed --class='Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder'
php artisan optimize:clear && php artisan config:cache route:cache view:cache
npm run build   # if views changed assets
```

- Seeder is idempotent — safe on every deploy.
- Creates XG holding company (if absent), HQ attach, DXB + TZ branches, department definitions, all permissions + role grants.
- Grant a human user the `group-administrator` role via the Identity user-management UI (or `User::find()->assignRole('group-administrator')`).
- No destructive migrations; safe to run on MySQL/MariaDB/SQLite.
- Rollback: `php artisan migrate:rollback` removes the 4 Phase-1 migrations only.
