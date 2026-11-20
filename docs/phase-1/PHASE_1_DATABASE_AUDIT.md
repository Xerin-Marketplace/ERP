# Phase 1 — Database Audit

| Table | Exists | Reuse/extend | Phase 1 change |
|---|---|---|---|
| companies | ✓ uuid, code uk, legal_name, type, parent, tin/vat, country, currency, timezone, status, created_by | reused | — |
| branches | ✓ code uk, company_id, type, contact, country, region, city, addresses, status, created_by | extended | +currency, +timezone, +country_code, +postal_code, +activated_at, +deactivated_at, +updated_by |
| locations | ✓ company+branch scoped, unique(company_id,code) | reused | — |
| department_definitions | ✓ code uk catalog | reused | — |
| departments | ✓ company_id, branch_id, parent_id, head_id, status | extended | +unique(branch_id, code) |
| job_positions (Employee) | ✓ code uk, title, company_id, department_id, grade, reports_to, status | reused as spec "positions" | — |
| positions | — | NOT created (would duplicate job_positions) | — |
| branch_settings | — | created | branch_id, key, value, type; unique(branch_id,key) |
| branch_administrators | — | created | branch_id, user_id, assigned_by, revoked_at; unique(branch_id,user_id) |
| employee_employment_records (Employee) | ✓ satisfies spec §6.2 assignment history contract | reused | — |
| users.branch_id | ✓ | scope basis | — |

FK enforcement on; critical columns indexed; no destructive migration written.
