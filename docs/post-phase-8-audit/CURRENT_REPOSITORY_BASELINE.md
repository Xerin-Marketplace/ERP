# Current Repository Baseline (verified)

- Repo: `/Users/group/Desktop/ITDEP/xerinerp` → `github.com/Xerin-Marketplace/ERP.git`
- Branch: `main` @ `8c19b0a` (audit point); working tree clean
- Stack: Laravel modular monolith, `nwidart/laravel-modules`, Sanctum, Spatie Permission, PHP 8.4
- Frontend: **Blade + Tailwind + Alpine** (not Next.js — Phase 0 deviation stands)
- DB: SQLite in-memory (tests) / PostgreSQL (server). 239 tables, 48+ migrations
- Routes: 703 total, 193 under `/api/v1`
- Modules enabled (16): Core, Organization, Identity, Employee, Ticketing, HR,
  Finance, SystemAdministration, Reception, Commercial, Cargo, Shipment,
  Arrival, Delivery, Portal, Sales
- Server: Contabo VPS `vmi3739504` (161.97.160.177), Plesk-managed Postfix
- Phase commits: P3 `1c0011e`, P4 `762c3d0`, P5 `83f0c85`, P6 `337a849`,
  P7 `42df2f1`, P8 `d64cabf` + post-P8 fixes (migration fix `69d3060`,
  dept seeding `53a405a`, receptionist role `6211370`, user mgmt `eb2e044`–`8c19b0a`)
- Test baseline: verified by execution (see TEST section of completion report)
- No separate frontend or mobile repositories exist (Phase 6 audit confirmed none)
