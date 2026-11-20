# Phase 1 — Test Report

## New suite: tests/Feature/OrganizationPhase1Test.php — 18 tests, 44 assertions, all passing

- Seeding: DXB (AED/Asia-Dubai/AE) + TZ (TZS/Dar-es-Salaam) present; re-seeding idempotent (1 row each).
- Branch ops: create via service, code unique (DB), activate/deactivate timestamps, non-destructive.
- Departments: code unique per branch enforced; same code allowed across branches.
- Positions: writes to `job_positions` (single source of truth).
- RBAC: non-privileged 403 on web create/deactivate; group-admin web activate/deactivate OK; scoped view policy true/false; administrator assignment expands scope.
- Guard: `AssignmentService::validatePlacement` throws ValidationException for suspended branch.
- Settings: typed roundtrip (json/string).
- API: lifecycle via Sanctum, 403 without perms, administrator assign 201.

## Full suite after Phase 1: 268 tests — 267 → 268/268 pass
(One stale assertion in pre-existing `OrganizationModuleTest` updated: branch count now includes seeded DXB/TZ.)

## Manual smoke (dev server, super-admin session)
- `/organization`, `/branches`, `/branches/{id}` (tabs), `/positions`, `/departments`, `/companies`, `/structure` → 200.
- Web deactivate → suspended + `deactivated_at`; activate → active + `activated_at`; administrator assign → row persisted.
