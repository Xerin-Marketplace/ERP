# Database Guide

## Conventions

- Primary keys: `id()` bigIncrements. ULID (`$table->ulid('id')->primary()`)
  allowed per-module where external exposure is expected — never mix in one table.
- Always: `$table->timestamps()`, foreign keys with explicit `->constrained()->cascadeOnDelete()`
  or `nullOnDelete()` as domain requires.
- Soft deletes: `softDeletes()` where records must be recoverable.
- Indexes: every FK + every column used in `where`/`order` filters.
- Money: `decimal('amount', 15, 2)` — never float.
- Migration names: `YYYY_MM_DD_His_create_<table>_table` / `add_<col>_to_<table>_table`.
- Transactions: multi-write ops inside `DB::transaction(...)`.
- Seeders: idempotent (`firstOrCreate`/`updateOrCreate`), `RolePermissionSeeder`
  is the reference implementation.

## Phase 0 tables

| Table | Purpose |
|---|---|
| users (+status) | Accounts with AccountStatus enum |
| password_reset_tokens, sessions | Breeze |
| roles, permissions, model_has_*, role_has_permissions | Spatie RBAC |
| personal_access_tokens | Sanctum |
| system_settings | Runtime config (key/value/type/group/encrypted) |
| audit_logs | Append-only audit trail |
| managed_files | File metadata for FileService |
| notifications | In-app notification channel |
| jobs, failed_jobs, cache | Database driver defaults |

## Backups

See `deploy.md` + `backup-recovery.md`. SQLite = file copy; MySQL = `mysqldump`.
