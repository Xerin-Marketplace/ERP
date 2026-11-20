# Document Centre Integration Audit

## What exists
- `managed_files` (id, user_id, disk, path, original_name, mime, size,
  visibility, metadata) — one generic store, local disk.
- Wired usages: `employee_documents.managed_file_id`, employee photo; customer
  documents table exists (`customer_documents`) for commercial docs.
- Per-domain doc tables exist separately: `shipment_documents`,
  `import_documents`, `access_request_documents`, `registration_documents`,
  `employee_documents`, `customer_documents`, `hr_documents`, `claim_evidence`
  (via events), `ticket_attachments`, `inquiry_attachments`.

## Classification
| Area | Wiring |
|---|---|
| Employee docs → managed_files | Fully wired |
| Customer documents | Partially (own table, local storage) |
| Shipment/AWB/import/claim docs | Partially — own tables, no shared centre |
| PO/supplier docs | Not wired (PO doesn't exist yet) |
| Versioning/expiry/review across entities | Not wired — schema has type/status fields but no unified lifecycle |
| S3/object storage | Blocked — disk configured, no bucket, no signed-url usage |

## Finding
"Document Centre" as a unified cross-entity feature is **PARTIAL**: storage
primitive exists (`managed_files`) but most modules keep their own doc tables
with no polymorphic link to managed_files, no unified download authorization,
no versioning. Phase 9 should adopt a convention: procurement docs attach via
managed_files or the existing per-domain pattern must be chosen deliberately.
