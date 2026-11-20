# Repository Inventory

| Field | Finding |
|---|---|
| Repository | `xerinerp` (local: `/Users/group/Desktop/ITDEP/xerinerp`, server: `/var/www/xerinerp`) |
| Remote | `https://github.com/Xerin-Marketplace/ERP.git` (single repo — no separate frontend repo found) |
| Branch | `main` — HEAD `19fc283` |
| Working tree | Clean at audit time |
| Technology | Laravel 11/12 (PHP 8.4), nwidart/laravel-modules, Sanctum, Spatie Permission, Blade/Tailwind/Alpine, Vite, PHPUnit |
| Modules | Core, Organization, Identity, Employee, HR, Ticketing, Finance, SystemAdministration, Reception (all enabled in `modules_statuses.json`) |
| Tests | 23 test files, **302 tests / 798 assertions, all passing** (executed, not claimed) |
| Server | Contabo `vmi3739504` / 161.97.160.177 — PostgreSQL `xerinerp`, nginx, deploy doc at `deploy.md` |
| Docs | `docs/phase-1..3`, `docs/hr-transformation`, `docs/reception` — phase audits exist |

**No Next.js/React repository exists.** The baseline assumes one — see OPEN_DECISIONS.
