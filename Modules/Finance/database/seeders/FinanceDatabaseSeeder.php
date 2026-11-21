<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\ChartOfAccount;
use Modules\Finance\Models\ExpenseCategory;
use Modules\Finance\Models\FinancialYear;
use Modules\Finance\Models\TaxCategory;
use Modules\Finance\Models\TaxRate;
use Modules\Finance\Services\AccountService;
use Modules\Finance\Services\FinancePeriodService;
use Modules\Organization\Models\Company;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FinanceDatabaseSeeder extends Seeder
{
    public const PERMISSIONS = [
        'finance.dashboard.view', 'finance.settings.manage',
        'finance.accounts.view', 'finance.accounts.create', 'finance.accounts.update', 'finance.accounts.approve',
        'finance.journals.view', 'finance.journals.create', 'finance.journals.submit',
        'finance.journals.approve', 'finance.journals.post', 'finance.journals.reverse',
        'finance.ledger.view', 'finance.payables.manage', 'finance.receivables.manage',
        'finance.invoices.manage', 'finance.expenses.manage', 'finance.payments.manage',
        'finance.banks.manage', 'finance.reconciliation.manage', 'finance.budgets.manage',
        'finance.tax.manage', 'finance.assets.manage', 'finance.reports.view',
        'finance.periods.close', 'finance.periods.reopen', 'finance.audit.view',
        'finance.interbranch.manage',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $roles = [
            'Finance and Accounting' => self::PERMISSIONS,
            'Auditor' => ['finance.dashboard.view', 'finance.accounts.view', 'finance.journals.view',
                'finance.ledger.view', 'finance.reports.view', 'finance.audit.view'],
        ];
        foreach ($roles as $name => $perms) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }

        // super-admin accumulates grants across modules — never sync here,
        // or this module's seeder would wipe permissions granted by others.
        Role::where('name', 'super-admin')->first()?->givePermissionTo(self::PERMISSIONS);

        $company = Company::first();
        if (! $company) return;

        // Minimal COA skeleton (configurable; Tanzanian-agnostic codes)
        $coa = [
            ['1000', 'Assets', 'asset', null, false],
            ['1100', 'Cash & Bank', 'asset', '1000', true],
            ['1200', 'Accounts Receivable', 'asset', '1000', true],
            ['1300', 'Fixed Assets', 'asset', '1000', true],
            ['1350', 'Accumulated Depreciation', 'asset', '1000', true],
            ['1400', 'Inter-branch Receivable', 'asset', '1000', true],
            ['2000', 'Liabilities', 'liability', null, false],
            ['2100', 'Accounts Payable', 'liability', '2000', true],
            ['2200', 'Tax Payable', 'liability', '2000', true],
            ['2300', 'Inter-branch Payable', 'liability', '2000', true],
            ['3000', 'Equity', 'equity', null, false],
            ['3100', 'Retained Earnings', 'equity', '3000', true],
            ['4000', 'Revenue', 'revenue', null, false],
            ['4100', 'Sales Revenue', 'revenue', '4000', true],
            ['4300', 'Inter-branch Handling Recovery', 'revenue', '4000', true],
            ['5000', 'Expenses', 'expense', null, false],
            ['5100', 'Operating Expenses', 'expense', '5000', true],
            ['5200', 'Depreciation Expense', 'expense', '5000', true],
            ['5300', 'Inter-branch Handling Charge', 'expense', '5000', true],
        ];
        $ids = [];
        foreach ($coa as [$code, $name, $type, $parent, $postable]) {
            $ids[$code] = ChartOfAccount::firstOrCreate(
                ['company_id' => $company->id, 'code' => $code],
                ['name' => $name, 'type' => $type, 'subtype' => null,
                 'parent_id' => $parent ? $ids[$parent] : null,
                 'normal_balance' => in_array($type, ['asset', 'expense']) ? 'debit' : 'credit',
                 'postable' => $postable, 'is_active' => true]
            )->id;
        }

        $accounts = app(AccountService::class);
        foreach ([
            'cash' => '1100', 'accounts_receivable' => '1200', 'accounts_payable' => '2100',
            'tax_payable' => '2200', 'tax_receivable' => '1100', 'retained_earnings' => '3100',
            'sales_revenue' => '4100', 'expense_default' => '5100',
            'depreciation_expense' => '5200', 'accumulated_depreciation' => '1350',
        ] as $key => $code) {
            $accounts->map($company->id, $key, $ids[$code]);
        }

        DB::table('financial_settings')->updateOrInsert(
            ['company_id' => $company->id],
            ['base_currency' => 'TZS', 'decimal_places' => 2, 'require_journal_approval' => true,
             'created_at' => now(), 'updated_at' => now()]
        );

        if (! FinancialYear::where('company_id', $company->id)->exists()) {
            app(FinancePeriodService::class)->createYear($company->id, 'FY '.now()->year, now()->startOfYear()->toDateString());
        }

        $expenseDefaults = [
            ['OFF', 'Office Expenses'], ['TRV', 'Transport'], ['ACM', 'Accommodation'],
            ['MLS', 'Meals'], ['COM', 'Communication'], ['UTL', 'Utilities'],
            ['EQP', 'Equipment'], ['MNT', 'Maintenance'], ['MKT', 'Marketing'],
            ['PRF', 'Professional Services'], ['OPS', 'Operational'],
        ];
        foreach ($expenseDefaults as [$code, $name]) {
            ExpenseCategory::firstOrCreate(
                ['company_id' => $company->id, 'code' => $code],
                ['name' => $name, 'account_id' => $ids['5100'], 'is_active' => true]
            );
        }

        $vat = TaxCategory::firstOrCreate(['company_id' => $company->id, 'code' => 'VAT'],
            ['name' => 'Value Added Tax', 'account_id' => $ids['2200']]);
        TaxRate::firstOrCreate(['category_id' => $vat->id, 'name' => 'VAT Standard'],
            ['rate' => 18.0000, 'inclusive' => false, 'effective_from' => '2020-01-01', 'is_active' => true]);
        $wht = TaxCategory::firstOrCreate(['company_id' => $company->id, 'code' => 'WHT'],
            ['name' => 'Withholding Tax', 'account_id' => $ids['2200']]);
        TaxRate::firstOrCreate(['category_id' => $wht->id, 'name' => 'WHT Services'],
            ['rate' => 5.0000, 'inclusive' => false, 'effective_from' => '2020-01-01', 'is_active' => true]);
    }
}
