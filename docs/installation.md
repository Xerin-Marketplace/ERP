# Installation & Environment Setup

## Requirements

- PHP 8.3+ (8.4 recommended), Composer 2.x, Node 20+
- MySQL 8 / MariaDB 10.6+ (SQLite supported for local dev)
- Redis 6+ (cache + queues in production)

## Fresh install

```bash
git clone https://github.com/Xerin-Marketplace/ERP.git xerinerp
cd xerinerp
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure `.env` (DB, Redis, APP_URL), then:

```bash
php artisan migrate --seed     # seeds roles, permissions, admin
npm run build                  # or: npm run dev
php artisan serve
```

Login: seeded admin (see `database/seeders/DatabaseSeeder.php`).
**Rotate the seeded password before production use.**

## Environment reference

| Variable | Local | Production |
|---|---|---|
| `APP_ENV` | local | production |
| `APP_DEBUG` | true | **false** |
| `APP_TIMEZONE` | Africa/Dar_es_Salaam | Africa/Dar_es_Salaam |
| `DB_CONNECTION` | sqlite | mysql |
| `CACHE_STORE` | database | redis |
| `QUEUE_CONNECTION` | database | redis |
| `SESSION_SECURE_COOKIE` | — | true |
| `ERP_CURRENCY` | TZS | TZS |
| `ERP_MAX_UPLOAD_MB` | 10 | per policy |
| `ERP_API_RATE_LIMIT` | 60/min | per policy |

## Queue worker (production)

```bash
php artisan queue:work redis --tries=3 --backoff=30
```

Supervisor unit recommended (see `deploy.md`).

## Verification checklist

```bash
php artisan about
php artisan module:list
php artisan route:list
php artisan test
curl -s http://localhost:8000/api/v1/health
```
