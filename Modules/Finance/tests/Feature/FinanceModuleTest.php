<?php

namespace Modules\Finance\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Modules\Employee\Database\Seeders\EmployeeDatabaseSeeder;
use Modules\Finance\Database\Seeders\FinanceDatabaseSeeder;
use Modules\Finance\Models\AssetCategory;
use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\BankTransaction;
use Modules\Finance\Models\Budget;
use Modules\Finance\Models\ChartOfAccount;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\ExpenseCategory;
use Modules\Finance\Models\ExpenseClaim;
use Modules\Finance\Models\FinancialPeriod;
use Modules\Finance\Models\FinancialYear;
use Modules\Finance\Models\GeneralLedgerEntry;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\TaxRate;
use Modules\Finance\Services\AccountService;
use Modules\Finance\Services\BankService;
use Modules\Finance\Services\BudgetService;
use Modules\Finance\Services\ExpenseService;
use Modules\Finance\Services\FinApprovalService;
use Modules\Finance\Services\FinanceDashboardService;
use Modules\Finance\Services\FinancePeriodService;
use Modules\Finance\Services\FixedAssetService;
use Modules\Finance\Services\JournalService;
use Modules\Finance\Services\LedgerService;
use Modules\Finance\Services\PayableService;
use Modules\Finance\Services\PaymentService;
use Modules\Finance\Services\ReceivableService;
use Modules\Finance\Services\TaxService;
use Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;
use Tests\TestCase;

class FinanceModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(OrganizationDatabaseSeeder::class);
        $this->seed(IdentityDatabaseSeeder::class);
        $this->seed(EmployeeDatabaseSeeder::class);
        $this->seed(FinanceDatabaseSeeder::class);
        \DB::table('financial_settings')->update(['require_journal_approval' => false]);
    }

    private function admin(): User
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole('super-admin');
        return $u;
    }

    private function finManager(): User
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole('Finance and Accounting');
        return $u;
    }

    private function accountant(): User
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole('Finance and Accounting');
        return $u;
    }

    private function company(): Company { return Company::where('code', 'XG')->firstOrFail(); }
    private function account(string $code): ChartOfAccount
    {
        return ChartOfAccount::where('company_id', $this->company()->id)->where('code', $code)->firstOrFail();
    }
    private function openPeriod(): FinancialPeriod
    {
        return FinancialPeriod::where('company_id', $this->company()->id)->where('status', 'open')->firstOrFail();
    }
    private function supplier(): Supplier
    {
        return Supplier::firstOrCreate(['company_id' => $this->company()->id, 'code' => 'SUP1'],
            ['name' => 'Test Supplier', 'payment_terms_days' => 30]);
    }
    private function customer(): Customer
    {
        return Customer::firstOrCreate(['company_id' => $this->company()->id, 'code' => 'CUS1'],
            ['name' => 'Test Customer']);
    }
    private function journalData(string $dr, string $cr, float $amt = 1000): array
    {
        return [
            'company_id' => $this->company()->id,
            'period_id' => $this->openPeriod()->id,
            'journal_date' => $this->openPeriod()->starts_on->toDateString(),
            'memo' => 'Test',
            'lines' => [
                ['account_id' => $this->account($dr)->id, 'debit' => $amt, 'credit' => 0],
                ['account_id' => $this->account($cr)->id, 'debit' => 0, 'credit' => $amt],
            ],
        ];
    }

    /* ============ DOUBLE-ENTRY ENGINE ============ */

    public function test_balanced_journal_posts_successfully(): void
    {
        $svc = app(JournalService::class);
        $u = $this->admin();
        $j = $svc->create($this->journalData('1100', '4100'), $u->id);
        $svc->submit($j, $u->id);
        $approver = $this->finManager();
        $svc->approve($j, $approver->id);
        $posted = $svc->post($j->fresh(), $u->id);

        $this->assertSame('posted', $posted->status);
        $this->assertEquals(2, GeneralLedgerEntry::where('journal_id', $j->id)->count());
    }

    public function test_unbalanced_journal_rejected(): void
    {
        $svc = app(JournalService::class);
        $u = $this->admin();
        $data = $this->journalData('1100', '4100', 1000);
        $data['lines'][1]['credit'] = 500;
        $j = $svc->create($data, $u->id);
        // post() enforces balance at posting time — validateLines only checks shape
        $this->expectException(ValidationException::class);
        $svc->post($j, $u->id);
    }

    public function test_inactive_account_rejected(): void
    {
        $this->account('1100')->update(['is_active' => false]);
        $svc = app(JournalService::class);
        $j = $svc->create($this->journalData('1100', '4100'), $this->admin()->id);
        $this->expectException(ValidationException::class);
        $svc->post($j, $this->admin()->id);
    }

    public function test_non_postable_parent_rejected(): void
    {
        $svc = app(JournalService::class);
        // '1000' Assets is non-postable parent
        $data = $this->journalData('1000', '4100');
        $j = $svc->create($data, $this->admin()->id);
        $this->expectException(ValidationException::class);
        $svc->post($j, $this->admin()->id);
    }

    public function test_journal_cannot_be_posted_twice(): void
    {
        $svc = app(JournalService::class);
        $u = $this->admin();
        $j = $svc->create($this->journalData('1100', '4100'), $u->id);
        $svc->post($j, $u->id);
        $this->expectException(ValidationException::class);
        $svc->post($j->fresh(), $u->id);
    }

    public function test_posted_journal_immutable(): void
    {
        $svc = app(JournalService::class);
        $u = $this->admin();
        $j = $svc->create($this->journalData('1100', '4100'), $u->id);
        $svc->post($j, $u->id);
        $this->expectException(ValidationException::class);
        $svc->updateLines($j->fresh(), [['account_id' => $this->account('1100')->id, 'debit' => 1, 'credit' => 0], ['account_id' => $this->account('4100')->id, 'debit' => 0, 'credit' => 1]]);
    }

    public function test_reversal_creates_counter_journal_and_preserves_integrity(): void
    {
        $svc = app(JournalService::class);
        $u = $this->admin();
        $j = $svc->create($this->journalData('1100', '4100', 750), $u->id);
        $svc->post($j, $u->id);

        $reversal = $svc->reverse($j->fresh(), $u->id);
        $this->assertSame('posted', $reversal->status);
        $this->assertSame('reversed', $j->fresh()->status);
        $this->assertSame('reversal', $reversal->type);

        // Net GL movement on account 1100 should be zero after reversal
        $gl = GeneralLedgerEntry::where('account_id', $this->account('1100')->id);
        $this->assertEquals(0.0, $gl->sum('debit') - $gl->sum('credit'));
    }

    public function test_locked_period_rejects_posting(): void
    {
        $period = $this->openPeriod();
        app(FinancePeriodService::class)->lock($period, $this->admin()->id);
        $svc = app(JournalService::class);
        $data = $this->journalData('1100', '4100');
        $data['period_id'] = $period->id; // explicitly the locked period
        $j = $svc->create($data, $this->admin()->id);
        $this->expectException(ValidationException::class);
        $svc->post($j, $this->admin()->id);
    }

    public function test_self_approval_blocked(): void
    {
        $svc = app(JournalService::class);
        $u = $this->admin();
        $j = $svc->create($this->journalData('1100', '4100'), $u->id);
        $svc->submit($j, $u->id);
        $this->expectException(ValidationException::class);
        $svc->approve($j->fresh(), $u->id);
    }

    /* ============ LEDGER & STATEMENTS ============ */

    public function test_trial_balance_balances(): void
    {
        $svc = app(JournalService::class);
        $u = $this->admin();
        $svc->post($svc->create($this->journalData('1100', '4100', 2000), $u->id), $u->id);
        $svc->post($svc->create($this->journalData('5100', '1100', 800), $u->id), $u->id);

        $tb = app(LedgerService::class)->trialBalance($this->company()->id);
        $this->assertEquals($tb['total_debit'], $tb['total_credit']);
        $this->assertEquals(2800.0, $tb['total_debit']);
    }

    public function test_ledger_running_balance(): void
    {
        $svc = app(JournalService::class);
        $u = $this->admin();
        $svc->post($svc->create($this->journalData('1100', '4100', 500), $u->id), $u->id);
        $svc->post($svc->create($this->journalData('5100', '1100', 200), $u->id), $u->id);

        $ledger = app(LedgerService::class)->accountLedger($this->account('1100')->id);
        $this->assertEquals(300.0, $ledger['closing']); // +500 -200
        $this->assertCount(2, $ledger['rows']);
    }

    public function test_gl_derived_from_posted_journals_only(): void
    {
        $svc = app(JournalService::class);
        $u = $this->admin();
        $svc->create($this->journalData('1100', '4100', 500), $u->id); // draft — no GL
        $this->assertEquals(0, GeneralLedgerEntry::count());
        $tb = app(LedgerService::class)->trialBalance($this->company()->id);
        $this->assertEquals(0, $tb['total_debit']);
    }

    /* ============ ACCOUNTS PAYABLE ============ */

    public function test_supplier_invoice_posting_creates_payable_entries(): void
    {
        $svc = app(PayableService::class);
        $u = $this->admin();
        $inv = $svc->createInvoice([
            'company_id' => $this->company()->id,
            'supplier_id' => $this->supplier()->id,
            'supplier_invoice_no' => 'EXT-001',
            'invoice_date' => $this->openPeriod()->starts_on->toDateString(),
            'lines' => [['description' => 'Office supplies', 'account_id' => $this->account('5100')->id, 'quantity' => 2, 'unit_price' => 500]],
        ], $u->id);
        $this->assertEquals(1000.0, (float) $inv->total);

        $posted = $svc->post($inv, $u->id);
        $this->assertSame('posted', $posted->status);

        // AP credited 1000
        $apId = $this->account('2100')->id;
        $this->assertEquals(1000.0, (float) GeneralLedgerEntry::where('account_id', $apId)->sum('credit'));
        $this->assertEquals(1000.0, (float) GeneralLedgerEntry::where('account_id', $this->account('5100')->id)->sum('debit'));
    }

    public function test_duplicate_supplier_invoice_no_detected(): void
    {
        SupplierInvoice::create([
            'reference' => 'XERIN-SINV-2026-000001', 'company_id' => $this->company()->id,
            'supplier_id' => $this->supplier()->id, 'supplier_invoice_no' => 'DUP-1',
            'invoice_date' => now(), 'total' => 100, 'created_by' => $this->admin()->id,
        ]);
        $this->expectException(\Illuminate\Database\QueryException::class);
        SupplierInvoice::create([
            'reference' => 'XERIN-SINV-2026-000002', 'company_id' => $this->company()->id,
            'supplier_id' => $this->supplier()->id, 'supplier_invoice_no' => 'DUP-1',
            'invoice_date' => now(), 'total' => 200, 'created_by' => $this->admin()->id,
        ]);
    }

    /* ============ ACCOUNTS RECEIVABLE ============ */

    public function test_customer_invoice_posting_creates_receivable_entries(): void
    {
        $svc = app(ReceivableService::class);
        $u = $this->admin();
        $inv = $svc->createInvoice([
            'company_id' => $this->company()->id,
            'customer_id' => $this->customer()->id,
            'invoice_date' => $this->openPeriod()->starts_on->toDateString(),
            'lines' => [['description' => 'Consulting', 'account_id' => $this->account('4100')->id, 'quantity' => 1, 'unit_price' => 3000]],
        ], $u->id);
        $posted = $svc->post($inv, $u->id);
        $this->assertSame('posted', $posted->status);
        $this->assertEquals(3000.0, (float) GeneralLedgerEntry::where('account_id', $this->account('1200')->id)->sum('debit'));
        $this->assertEquals(3000.0, (float) GeneralLedgerEntry::where('account_id', $this->account('4100')->id)->sum('credit'));
    }

    /* ============ PAYMENTS & ALLOCATION ============ */

    public function test_payment_allocation_and_partial_settlement(): void
    {
        $u = $this->admin();
        $paySvc = app(PaymentService::class);
        $recSvc = app(ReceivableService::class);
        $inv = $recSvc->createInvoice([
            'company_id' => $this->company()->id, 'customer_id' => $this->customer()->id,
            'invoice_date' => $this->openPeriod()->starts_on->toDateString(),
            'lines' => [['description' => 'X', 'account_id' => $this->account('4100')->id, 'quantity' => 1, 'unit_price' => 1000]],
        ], $u->id);
        $recSvc->post($inv, $u->id);

        // Partial payment 400
        $p = $paySvc->record([
            'company_id' => $this->company()->id, 'direction' => 'in', 'method' => 'bank_transfer',
            'amount' => 400, 'payment_date' => $this->openPeriod()->starts_on->toDateString(),
            'document_type' => 'customer_invoice', 'document_id' => $inv->id,
        ], $u->id);
        $this->assertSame('partially_paid', $inv->fresh()->status);
        $this->assertEquals(400.0, (float) $inv->fresh()->received_amount);

        // Full payment of remainder
        $paySvc->record([
            'company_id' => $this->company()->id, 'direction' => 'in', 'method' => 'cash',
            'amount' => 600, 'payment_date' => $this->openPeriod()->starts_on->toDateString(),
            'document_type' => 'customer_invoice', 'document_id' => $inv->id,
        ], $u->id);
        $this->assertSame('paid', $inv->fresh()->status);
    }

    public function test_overpayment_rejected(): void
    {
        $u = $this->admin();
        $recSvc = app(ReceivableService::class);
        $inv = $recSvc->createInvoice([
            'company_id' => $this->company()->id, 'customer_id' => $this->customer()->id,
            'invoice_date' => $this->openPeriod()->starts_on->toDateString(),
            'lines' => [['description' => 'X', 'account_id' => $this->account('4100')->id, 'quantity' => 1, 'unit_price' => 100]],
        ], $u->id);
        $recSvc->post($inv, $u->id);
        $this->expectException(ValidationException::class);
        app(PaymentService::class)->record([
            'company_id' => $this->company()->id, 'direction' => 'in', 'method' => 'cash',
            'amount' => 500, 'payment_date' => $this->openPeriod()->starts_on->toDateString(),
            'document_type' => 'customer_invoice', 'document_id' => $inv->id,
        ], $u->id);
    }

    /* ============ EXPENSES ============ */

    public function test_expense_requires_approval_before_posting(): void
    {
        $u = $this->admin();
        $svc = app(ExpenseService::class);
        $cat = ExpenseCategory::where('company_id', $this->company()->id)->first();
        $claim = $svc->submit([
            'company_id' => $this->company()->id, 'category_id' => $cat->id,
            'expense_date' => $this->openPeriod()->starts_on->toDateString(),
            'description' => 'Fuel', 'amount' => 150,
        ], $u->id);

        $this->expectException(ValidationException::class);
        $svc->post($claim, $u->id);
    }

    public function test_expense_posts_after_approval(): void
    {
        $creator = $this->admin();
        $approver = $this->finManager();
        $svc = app(ExpenseService::class);
        $cat = ExpenseCategory::where('company_id', $this->company()->id)->first();
        $claim = $svc->submit([
            'company_id' => $this->company()->id, 'category_id' => $cat->id,
            'expense_date' => $this->openPeriod()->starts_on->toDateString(),
            'description' => 'Fuel', 'amount' => 150,
        ], $creator->id);

        $approval = $claim->approvals->first();
        app(FinApprovalService::class)->decide($approval, $approver->id, true);
        $posted = $svc->post($claim->fresh(), $creator->id);
        $this->assertSame('posted', $posted->status);
        $this->assertEquals(150.0, (float) GeneralLedgerEntry::where('account_id', $this->account('5100')->id)->sum('debit'));
    }

    /* ============ BANK & RECONCILIATION ============ */

    public function test_bank_reconciliation_identifies_differences(): void
    {
        $u = $this->admin();
        $svc = app(BankService::class);
        $bank = BankAccount::create([
            'company_id' => $this->company()->id, 'account_id' => $this->account('1100')->id,
            'name' => 'Main Account', 'opening_balance' => 10000,
        ]);
        // Book: post 500 into cash account
        app(JournalService::class)->post(
            app(JournalService::class)->create($this->journalData('1100', '4100', 500), $u->id), $u->id);
        $this->assertEquals(10500.0, $svc->bookBalance($bank));

        // Statement shows only 10200 (missing 300 deposit)
        BankTransaction::create(['bank_account_id' => $bank->id, 'txn_date' => now(), 'description' => 'Dep', 'credit' => 200]);
        $recon = $svc->reconcile($bank, now()->toDateString(), 10200.0, $u->id);
        $this->assertEquals(-300.0, (float) $recon->difference);
    }

    /* ============ BUDGET ============ */

    public function test_budget_block_policy(): void
    {
        $u = $this->admin();
        $svc = app(BudgetService::class);
        $year = FinancialYear::where('company_id', $this->company()->id)->first();
        $budget = $svc->create([
            'company_id' => $this->company()->id, 'year_id' => $year->id, 'name' => 'IT Budget',
            'control_policy' => 'block',
            'lines' => [['account_id' => $this->account('5100')->id, 'amount' => 1000]],
        ], $u->id);
        $svc->submit($budget, $u->id);
        $approver = $this->finManager();
        app(FinApprovalService::class)->decide($budget->approvals->first(), $approver->id, true);
        $budget = $svc->activate($budget->fresh());

        $r1 = $svc->consume($budget, $this->account('5100')->id, 600);
        $this->assertTrue($r1['ok']);
        $r2 = $svc->consume($budget, $this->account('5100')->id, 500); // 600+500 > 1000
        $this->assertFalse($r2['ok']);
        $this->assertSame('blocked_over_budget', $r2['action']);
    }

    public function test_budget_warn_policy_allows_overage(): void
    {
        $u = $this->admin();
        $svc = app(BudgetService::class);
        $year = FinancialYear::where('company_id', $this->company()->id)->first();
        $budget = $svc->create([
            'company_id' => $this->company()->id, 'year_id' => $year->id, 'name' => 'Ops',
            'control_policy' => 'warn',
            'lines' => [['account_id' => $this->account('5100')->id, 'amount' => 100]],
        ], $u->id);
        $svc->submit($budget, $u->id);
        app(FinApprovalService::class)->decide($budget->approvals->first(), $this->finManager()->id, true);
        $budget = $svc->activate($budget->fresh());
        $r = $svc->consume($budget, $this->account('5100')->id, 150);
        $this->assertTrue($r['ok']);
        $this->assertSame('warned', $r['action']);
    }

    /* ============ TAX ============ */

    public function test_tax_computation_preserves_rate_snapshot(): void
    {
        $svc = app(TaxService::class);
        $rate = TaxRate::whereHas('category', fn ($q) => $q->where('code', 'VAT'))->first();
        $tax = $svc->record($this->company()->id, $rate, 'test_doc', 1, 1000);
        $this->assertEquals(180.0, (float) $tax->tax_amount);
        $this->assertEquals(18.0, (float) $tax->rate_applied);
    }

    /* ============ FIXED ASSETS ============ */

    public function test_depreciation_cannot_post_twice(): void
    {
        $u = $this->admin();
        $svc = app(FixedAssetService::class);
        $cat = AssetCategory::create([
            'company_id' => $this->company()->id, 'code' => 'VEH', 'name' => 'Vehicles',
            'depreciation_account_id' => $this->account('5200')->id,
            'accumulated_account_id' => $this->account('1350')->id,
            'useful_life_months' => 12, 'method' => 'straight_line',
        ]);
        $asset = $svc->register([
            'company_id' => $this->company()->id, 'category_id' => $cat->id,
            'name' => 'Van', 'acquired_on' => $this->openPeriod()->starts_on->toDateString(),
            'cost' => 12000, 'residual_value' => 0,
        ]);
        $schedule = $asset->schedules->first();
        $this->assertEquals(1000.0, (float) $schedule->amount);

        $svc->postDepreciation($schedule, $u->id);
        $this->assertEquals(1000.0, (float) $asset->fresh()->accumulated_depreciation);
        $this->assertEquals(1000.0, (float) GeneralLedgerEntry::where('account_id', $this->account('5200')->id)->sum('debit'));

        $this->expectException(ValidationException::class);
        $svc->postDepreciation($schedule->fresh(), $u->id);
    }

    /* ============ DASHBOARD ============ */

    public function test_dashboard_metrics_from_posted_data(): void
    {
        $u = $this->admin();
        $svc = app(JournalService::class);
        $svc->post($svc->create($this->journalData('1100', '4100', 5000), $u->id), $u->id);   // revenue 5000
        $svc->post($svc->create($this->journalData('5100', '1100', 1200), $u->id), $u->id);   // expense 1200

        $m = app(FinanceDashboardService::class)->metrics($this->company()->id);
        $this->assertEquals(5000.0, $m['total_revenue']);
        $this->assertEquals(1200.0, $m['total_expenses']);
        $this->assertEquals(3800.0, $m['net_profit']);
        $this->assertEquals(2, $m['journals']['posted']);
    }

    /* ============ ORGANIZATIONAL ISOLATION ============ */

    public function test_cross_company_accounts_invisible(): void
    {
        // Accounts belong to XG company only
        $other = Company::create(['code' => 'OTHR', 'legal_name' => 'Other Co', 'status' => 'active', 'currency' => 'TZS']);
        $otherAccount = ChartOfAccount::create([
            'company_id' => $other->id, 'code' => '9000', 'name' => 'Other', 'type' => 'asset',
            'normal_balance' => 'debit', 'postable' => true,
        ]);
        $u = $this->admin();
        Sanctum::actingAs($u);
        // API accounts endpoint returns only own-company accounts
        $resp = $this->getJson('/api/v1/finance/accounts');
        $resp->assertOk();
        $codes = collect($resp->json('data'))->pluck('code');
        $this->assertNotContains('9000', $codes);
        $this->assertContains('1100', $codes);
    }

    /* ============ API ============ */

    public function test_api_journal_lifecycle(): void
    {
        $u = $this->admin();
        Sanctum::actingAs($u);
        $resp = $this->postJson('/api/v1/finance/journals', [
            'period_id' => $this->openPeriod()->id,
            'journal_date' => $this->openPeriod()->starts_on->toDateString(),
            'lines' => [
                ['account_id' => $this->account('1100')->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->account('4100')->id, 'debit' => 0, 'credit' => 100],
            ],
        ]);
        $resp->assertCreated();
        $id = $resp->json('data.id');

        $this->postJson("/api/v1/finance/journals/{$id}/post")->assertOk();
        $this->assertSame('posted', Journal::find($id)->status);
    }

    public function test_api_dashboard(): void
    {
        Sanctum::actingAs($this->admin());
        $this->getJson('/api/v1/finance/dashboard')->assertOk()->assertJsonStructure([
            'success', 'data' => ['total_revenue', 'total_expenses', 'net_profit', 'bank_balance'],
        ]);
    }

    /* ============ RBAC ============ */

    public function test_unauthorized_user_cannot_post_journal(): void
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole('employee');
        Sanctum::actingAs($u);
        $j = app(JournalService::class)->create($this->journalData('1100', '4100'), $this->admin()->id);
        // Web route should 403 (can:finance.journals.post)
        $this->post("/finance/journals/{$j->id}/post")->assertForbidden();
    }

    public function test_api_rejects_unbalanced_journal_at_post(): void
    {
        Sanctum::actingAs($this->admin());
        $resp = $this->postJson('/api/v1/finance/journals', [
            'period_id' => $this->openPeriod()->id,
            'journal_date' => $this->openPeriod()->starts_on->toDateString(),
            'lines' => [
                ['account_id' => $this->account('1100')->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->account('4100')->id, 'debit' => 0, 'credit' => 50],
            ],
        ])->assertCreated();
        $id = $resp->json('data.id');
        $this->postJson("/api/v1/finance/journals/{$id}/post")->assertStatus(422);
        $this->assertSame(0, GeneralLedgerEntry::where('journal_id', $id)->count()); // full rollback
    }
}
