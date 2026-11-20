# WORK CENTER — REPORTING, DOCUMENT & WORK MANAGEMENT (Phase 1)

New module: `Modules/WorkCenter` — reuses `FileService`/`ManagedFile`,
`AuditService`, Spatie permissions, Organization branch/company scoping.
PDF engine: **barryvdh/laravel-dompdf** (installed).

## Implemented + verified (13/13 tests)
- **Reports** — `XER-REP-YYYY-NNNNNN` refs; builder (JSON section content)
  + manual upload (FileService validated) — both version-controlled,
  NEVER overwritten (`report_versions` unique report+version)
- **Approval** — draft→in_review→approved/rejected/returned→published;
  owner cannot self-review; only approved can publish
- **Visibility** — private/reviewers/department/branch/company, server-side
  `canView()` + `scopeVisibleTo()`; super-admin global
- **PDF** — corporate header (company/branch/ref/classification), cover
  page, template-driven sections, signature strip, running footer with
  `counter(page)/counter(pages)`, classification watermark
- **Documents** — `XER-DOC-` refs, central DMS, `nullableMorphs attachable`
  links docs to any ERP entity (tender/contract/employee…), versioning,
  sharing (view/download), expiry tracking (`expiryInDays`), branch/company
  visibility
- **Tasks** — `XER-TASK-` refs; assign→accept→in_progress→under_review→
  completed|revision_required; **100% progress ≠ completed** when review
  required; progress history never overwritten; assignee can't self-review;
  overdue detection; branch-scope assignment check

## Permissions (11 new)
work.reports.create/review/publish · work.documents.upload/share/manage ·
work.tasks.assign/review/manage · work.analytics.view · work.templates.manage
Seeded to super-admin (all), branch-admin (branch ops), department-manager.

## Deferred (documented, not claimed)
- Rich Blade workspace UI (builder editor, dashboards, calendars) — API-first
- Notifications wiring to EmployeeNotification channels
- Configurable approval matrix per category/branch; delegation/escalation
- Bulk upload, tags search UI, retention rules, signed URLs
- Calendar views, team tasks (multi-assignee), workload analytics screens
- Excel export, malware-scan integration

## Tables: 12 new — report_categories, report_templates, reports,
report_versions, report_reviews, document_categories, documents,
document_versions, document_shares, work_tasks, task_progress_updates, task_reviews
