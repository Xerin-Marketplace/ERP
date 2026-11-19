# Phase 6 — Deployment Guide

```bash
php artisan migrate            # creates 33 finance tables
php artisan module:seed Finance  # permissions, roles, COA skeleton, FY+12 periods, mappings, tax defaults, expense categories
```

Then per company:
1. `Finance → Settings`: verify base currency (default TZS), approval flag.
2. Review/extend the COA (codes 1000–5200 skeleton is a starting point, not a mandate).
3. Confirm `account_mappings` for cash/ar/ap/tax/depreciation.
4. Create financial year via `FinancePeriodService::createYear` if seeding didn't cover the needed range.
5. Configure bank accounts (map each to a GL cash account).
6. Assign finance roles to users.

Rollback-safe: migrations drop only finance tables; no changes to existing tables.
