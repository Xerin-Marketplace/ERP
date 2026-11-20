# ADMIN DATABASE DESIGN

Migration `2026_12_14_000001_extend_branch_administrators`:
adds delegable_roles, delegable_permissions (json), status,
expires_at, suspended_at, suspended_by to existing
`branch_administrators` (user_id, branch_id, assigned_by, revoked_*).

One delegation row per user (updateOrCreate on user_id).
Roles/permissions remain Spatie-managed — no second auth engine.
