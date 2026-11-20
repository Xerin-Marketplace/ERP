# HR Database Architecture (transformation additions)

Migration `2026_12_02_000001` (Modules/HR):

## employee_personal_details (extended)
`marital_status`, `passport_number` (text), `passport_expiry`, `work_permit_number`, `work_permit_expiry`, `country`, `region`, `city`, `postal_address`, `next_of_kin_name/phone/relationship`

## employee_bank_accounts
employee FK · holder/bank/branch · `account_number`+`iban` = **text + encrypted cast** · swift_bic · currency · country · payment_method (bank_transfer|mobile_money|cash|cheque) · payment_frequency · verification_status (pending|verified|rejected) · verified_by/at · is_primary · softDeletes

## employee_compensations (immutable revision history)
employee FK · basic + 6 allowance columns + overtime_rate + 4 deduction columns · currency · pay_frequency · status (pending|approved|superseded|rejected) · effective_date · reason · requested_by · approved_by/at · supersedes_id (self-FK chain)

## payroll_periods
name+branch_id unique · start/end · status (open|processing|approved|closed) · closed_by/at

## payroll_runs
period+employee unique · `reference` (XERIN-PAY-YYYY-NNNNNN) · gross/deductions/net · currency · status (draft|approved|paid|reversed) · `breakdown` JSON snapshot · processed/approved/paid audit
