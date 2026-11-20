# Phase 3 Database Schema

## employee_personal_details (changed)

| Column | Change |
|---|---|
| `national_id` | `string(60)` → `text` (migration `2026_12_01_000001`) — holds AES ciphertext via the model's `encrypted` cast |

All other Phase 3 tables pre-existed:

- `employees` — number (unique), user_id (unique FK), status, employment_type, start/confirmation/contract_end/separation dates, placement FKs (company/branch/department/job_position/manager), softDeletes
- `employee_personal_details` — names, gender, DOB, nationality, contact, national_id (encrypted)
- `employee_emergency_contacts`, `employee_document_categories`, `employee_documents`
- `employee_events` — type, description, metadata JSON, actor_user_id
- `employee_employment_records` — contract/record history
- `employee_reporting_relationships`, `job_positions`, `job_grades`
- `employee_transfer_requests`, `employee_change_requests`
- `employee_onboarding_records`/`_tasks`, `employee_offboarding_records`
- `employee_assignments` (Identity) — placement history

Employee number format: `EMP-{BRANCH_CODE}-{######}` (e.g. `EMP-TZ-000001`), or legacy `XERIN-EMP-{YYYY}-{######}` when no branch.
