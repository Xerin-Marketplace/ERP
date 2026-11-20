# Procurement (Phase 9) Readiness Assessment

## Dependencies check
| Dependency | Status | Evidence |
|---|---|---|
| Supplier master | PARTIAL | `suppliers` (Finance): code, company_id, name, email, phone, tin, address, payment_terms_days, is_active — usable but thin (no categories/verification/docs) |
| AP side | EXISTS | `supplier_invoices` + lines, `PayableService`, `payment_records` direction, journals/GL |
| Finance journals | EXISTS | chart_of_accounts, journals, journal_lines, general_ledger_entries, finance_approvals |
| Branch isolation | EXISTS | company/branch columns + scope conventions |
| Approval pattern | EXISTS | finance_approvals, ticket approvals, pricing_approvals, access workflow engine (Reception) — reuse pattern, not a shared engine |
| Warehouse/GRN linkage | PARTIAL | warehouses/locations/movements exist for cargo; no goods-receiving for purchases |
| Document attach | PARTIAL | managed_files or per-domain table — decide convention |
| Notifications | PARTIAL | schema-ready (`customer_messages`), no transport |

## Readiness verdict
Score context: foundation ~70% ready — all heavy plumbing (org, RBAC, finance
posting, transitions, tests) is proven. Missing: the entire purchasing
workflow itself (requisitions→RFQ→PO→GRN→3-way match) and supplier lifecycle
fields. These are greenfield tables, not refactors — **low regression risk**.

## Recommended Phase 9 order
1. Supplier lifecycle extension (categories, verification, docs) on existing `suppliers`.
2. Requisitions + approval (reuse approval patterns).
3. RFQ + supplier quotations + comparison.
4. Purchase Orders + versioning + approval.
5. GRN + partial receiving (warehouse reuse).
6. Invoice matching (PO-GRN-INV) posting to existing supplier_invoices/journals.
7. Payments via payment_records; supplier statements read Finance.
