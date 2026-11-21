<?php

namespace Modules\Finance\Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Commercial\Models\Customer;
use Modules\Employee\Database\Seeders\EmployeeDatabaseSeeder;
use Modules\Finance\Database\Seeders\FinanceDatabaseSeeder;
use Modules\Finance\Jobs\TransmitEfdReceipt;
use Modules\Finance\Models\ChartOfAccount;
use Modules\Finance\Models\EfdReceipt;
use Modules\Finance\Models\FinancialPeriod;
use Modules\Finance\Services\EfdReceiptService;
use Modules\Finance\Services\ReceivableService;
use Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder;
use Modules\Organization\Models\Company;
use Tests\TestCase;

class EfdReceiptTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(OrganizationDatabaseSeeder::class);
        $this->seed(IdentityDatabaseSeeder::class);
        $this->seed(EmployeeDatabaseSeeder::class);
        $this->seed(FinanceDatabaseSeeder::class);
        \DB::table('financial_settings')->update(['require_journal_approval' => false]);
        $this->company = Company::where('code', 'XG')->firstOrFail();
        $this->user = User::factory()->create(['status' => AccountStatus::Active]);
        $this->user->assignRole('Finance and Accounting');
    }

    private function postedInvoice()
    {
        $customer = new Customer(['company_id' => $this->company->id,
            'name' => 'Receipt Customer', 'tin' => 'TIN-99', 'status' => 'active']);
        $customer->forceFill(['code' => 'CUS1']);
        $customer->save();
        $account = ChartOfAccount::where('company_id', $this->company->id)
            ->where('code', '4100')->firstOrFail();
        $period = FinancialPeriod::where('company_id', $this->company->id)
            ->where('status', 'open')->firstOrFail();

        $svc = app(ReceivableService::class);
        $inv = $svc->createInvoice([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'invoice_date' => $period->starts_on->toDateString(),
            'lines' => [['description' => 'Freight', 'account_id' => $account->id,
                'quantity' => 1, 'unit_price' => 500]],
        ], $this->user->id);
        return $svc->post($inv, $this->user->id);
    }

    public function test_posting_invoice_drafts_efd_receipt_with_payload(): void
    {
        $inv = $this->postedInvoice();
        $receipt = EfdReceipt::where('customer_invoice_id', $inv->id)->firstOrFail();

        $this->assertSame('draft', $receipt->status);
        $this->assertMatchesRegularExpression('/^EFD-\d{4}-\d{6}$/', $receipt->reference);
        $p = $receipt->payload;
        $this->assertSame($inv->reference, $p['invoice_reference']);
        $this->assertSame('Receipt Customer', $p['customer']['name']);
        $this->assertSame('TIN-99', $p['customer']['tin']);
        $this->assertEquals(500.0, $p['totals']['total']);
        $this->assertSame('Freight', $p['items'][0]['description']);
    }

    public function test_queue_keeps_receipt_local_when_gateway_not_configured(): void
    {
        Queue::fake();
        $receipt = EfdReceipt::first(); // none yet
        $inv = $this->postedInvoice();
        $receipt = EfdReceipt::where('customer_invoice_id', $inv->id)->firstOrFail();

        app(EfdReceiptService::class)->queue($receipt);
        $this->assertSame('queued', $receipt->fresh()->status);
        $this->assertNotNull($receipt->fresh()->queued_at);
        Queue::assertNotPushed(TransmitEfdReceipt::class); // disabled by default
    }

    public function test_enabled_but_no_gateway_marks_failed_not_transmitted(): void
    {
        app(\App\Services\SettingsService::class)->set('finance.efd.enabled', '1', 'finance', 'bool');
        $inv = $this->postedInvoice();
        $receipt = EfdReceipt::where('customer_invoice_id', $inv->id)->firstOrFail();

        app(EfdReceiptService::class)->queue($receipt);
        // job dispatched — run it synchronously
        (new TransmitEfdReceipt($receipt->fresh()))->handle();

        $r = $receipt->fresh();
        $this->assertSame('failed', $r->status);
        $this->assertSame('gateway_not_configured', $r->response['error']);
        $this->assertSame(1, $r->attempts);
    }

    public function test_void_requires_reason_and_blocks_transmitted(): void
    {
        $inv = $this->postedInvoice();
        $receipt = EfdReceipt::where('customer_invoice_id', $inv->id)->firstOrFail();

        app(EfdReceiptService::class)->void($receipt, 'Wrong amounts');
        $this->assertSame('void', $receipt->fresh()->status);

        $receipt->update(['status' => 'transmitted']);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(EfdReceiptService::class)->void($receipt->fresh(), 'x');
    }

    public function test_receipt_issue_is_idempotent_per_invoice(): void
    {
        $inv = $this->postedInvoice();
        $svc = app(EfdReceiptService::class);
        $a = $svc->issue($inv);
        $b = $svc->issue($inv);
        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, EfdReceipt::where('customer_invoice_id', $inv->id)->count());
    }
}
