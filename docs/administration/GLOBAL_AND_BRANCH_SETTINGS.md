# GLOBAL vs BRANCH SETTINGS

- GLOBAL (`admin.settings.global` / `settings.manage`): company identity,
  security policies, module registry, integrations — super-admin only.
  Existing SystemAdministration screens already enforce this tier.
- BRANCH (`admin.settings.branch`): branch contact/ops settings —
  super-admin + delegated branch-admin.
- Inheritance: branch settings never overwrite global keys; a branch row
  is an override scoped by `branch_id`, read-after-global fallback.
