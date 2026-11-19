# Security Standards

## Baseline implemented

- **AuthN**: bcrypt passwords, throttled login, CSRF tokens, session regeneration.
- **AuthZ**: server-side `permission`/`role` middleware + policies — never UI-only.
- **Account status gate**: non-`active` users rejected on every protected request.
- **API**: Sanctum bearer tokens, `throttle:api` rate limiting, 404/401/403 JSON
  envelope (no stack traces leaked — `APP_DEBUG=false` in production).
- **Headers** (nginx): `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`;
  `X-Powered-By` hidden; dotfiles denied; 30-day static cache.
- **Transport**: Let's Encrypt TLS, HTTP→HTTPS redirect, `SESSION_SECURE_COOKIE`.
- **Injection**: Eloquent/bindings only — no raw concatenated queries.
- **Uploads**: FileService whitelist of mime/extension + size cap + private disk.
- **Secrets**: `.env` only; `system_settings` supports `encrypt:true` for
  sensitive runtime values. Seeded admin password must be rotated.
- **Audit**: login/logout/failed-auth + service calls recorded with IP/UA;
  `audit_logs` is append-only.
- **Dependencies**: `composer audit` — 0 advisories (run in CI + before releases).

## Policies

- Rotate all seeded/default credentials before go-live.
- MFA: extension point reserved for Phase 1+ (do not bolt on ad-hoc now).
- Principle of least privilege for module permissions — `*.manage` vs `*.view`.
- Report security issues privately — never in public issues.
