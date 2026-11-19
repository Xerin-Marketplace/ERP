# Backup & Recovery

## What to back up

| Asset | Location | Method |
|---|---|---|
| Database | MySQL `xerinerp` | `mysqldump --single-transaction` |
| SQLite (dev/VPS) | `database/database.sqlite` | File copy while quiet |
| Uploads | `storage/app/` | rsync/tar |
| Env | `.env` | Secure secrets store (never git) |
| Code | GitHub | `git push` — always |

## Daily backup (cron)

```bash
# /etc/cron.d/xerinerp-backup
0 2 * * * root mysqldump --single-transaction xerinerp | gzip > /backups/db-$(date +\%F).sql.gz
15 2 * * * root tar -czf /backups/storage-$(date +\%F).tar.gz -C /var/www/xerinerp/storage app
```

Retain 14 days local + ship to off-site (S3/rsync).

## Restore

```bash
# DB
gunzip -c /backups/db-YYYY-MM-DD.sql.gz | mysql xerinerp
# or SQLite:
cp backup.sqlite /var/www/xerinerp/database/database.sqlite

# Files
tar -xzf /backups/storage-YYYY-MM-DD.tar.gz -C /var/www/xerinerp/storage

php artisan optimize:clear && php artisan optimize
systemctl reload php8.4-fpm
```

## Verify

Quarterly: restore to staging and run `php artisan test` + login smoke test.
A backup that has never been restored is not a backup.
