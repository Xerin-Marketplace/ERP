# DevOps & Infrastructure Audit

- Contabo VPS `vmi3739504` (161.97.160.177); nginx; PHP 8.4; PostgreSQL 15+ (`xerinerp`/`xerin`); deploy doc `deploy.md`.
- Deployment = manual `git pull` + artisan caches + `npm run build` (build/ is gitignored — rebuild required server-side).
- `composer dump-autoload` needed whenever a NEW module ships (provider discovery) — document in deploy runbook.
- `modules_statuses.json` auto-rewrites on server — recurring git conflict; checkout-then-pull workaround documented.

## Gaps
- No CI/CD (no .github/workflows) — manual deploys only.
- No automated backup verification (backups UI exists; schedule+offsite unverified).
- Redis not installed — queue/cache on `database` driver.
- No monitoring/alerting beyond sysadmin.monitoring page.
- Postgres password `mc544aar` was shared in-chat — **rotate** (risk register R-01).
