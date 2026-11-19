# Phase 6 — Accounting Rules

1. **Double entry**: Σdebit = Σcredit (2dp rounding); ≥2 lines; each line exactly one of debit/credit > 0.
2. **Normal balances**: asset/expense → debit; liability/equity/revenue → credit. Account default derived from type if not given.
3. **Postable guard**: non-postable parent accounts reject direct postings.
4. **Period gate**: posting requires `financial_periods.status = open` and journal_date inside the period. Closed → reopenable (authorized + audited); locked → hard stop.
5. **Immutability**: posted journals cannot be edited or deleted; corrections only via reversal journals (`type=reversal`, swapped lines, auto-posted, `reversal_of_id`/`reversed_by_id` links).
6. **Idempotent posting**: status checked under row lock; `posted_at` guard + unique GL line key.
7. **Document postings**:
   - Supplier invoice: Dr expense/asset lines, Dr input-tax (if any), Cr AP.
   - Customer invoice: Dr AR, Cr revenue lines, Cr output-tax.
   - Payment in: Dr cash/bank, Cr AR. Payment out: Dr AP, Cr cash/bank.
   - Expense: Dr category account, Cr cash.
   - Depreciation: Dr depreciation expense, Cr accumulated depreciation.
8. **Control accounts** resolved via `account_mappings` (per company): cash, accounts_receivable, accounts_payable, tax_payable, tax_receivable, expense_default, depreciation_expense, accumulated_depreciation, sales_revenue.
9. **Budgets** never create GL entries; they consume against `budget_lines.consumed` and report variance vs actual GL spend.
10. **Rounding**: line amounts rounded to 2dp at creation; comparisons use ±0.001 tolerance.
