<?php

namespace Modules\Finance\Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Employee\Database\Seeders\EmployeeDatabaseSeeder;
use Modules\Finance\Database\Seeders\FinanceDatabaseSeeder;
use Modules\Finance\Models\ChartOfAccount;
use Modules\Finance\Models\InterBranchEntry;
use Modules\Finance\Services\InterBranchService;
use Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Tests\TestCase;

class InterBranchTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $dxb;
    private Branch $tz;
    private User $finance;

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
        $this->dxb = Branch::firstOrCreate(['company_id' => $this->company->id, 'code' => 'DXB'],
            ['name' => 'Dubai', 'status' => 'active']);
        $this->tz = Branch::firstOrCreate(['company_id' => $this->company->id, 'code' => 'TZ'],
            ['name' => 'Tanzania', 'status' => 'active']);
        $this->finance = User::factory()->create(['status' => AccountStatus::Active]);
        $this->finance->assignRole('Finance and Accounting');
    }

    public function test_record_and_reconcile_month(): void
    {
        $svc = app(InterBranchService::class);
        $svc->record($this->company->id, $this->dxb->id, $this->tz->id,
            150.0, 'AED', 'Passenger fee TR-1', 'cargo_trip', 1, $this->finance->id);
        $e2 = $svc->record($this->company->id, $this->dxb->id, $this->tz->id,
            40.0, 'AED', 'Packing materials', 'manual', null, $this->finance->id);
        $this->assertMatchesRegularExpression('/^IB-\d{4}-\d{6}$/', $e2->reference);

        $rec = $svc->reconciliation($this->company->id, now()->year, now()->month);
        $this->assertCount(2, $rec['entries']);
        $pair = $rec['pairs']->first();
        $this->assertSame(190.0, $pair['open_total']);
        $this->assertSame(0.0, $pair['settled_total']);
    }

    public function test_same_branch_rejected(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(InterBranchService::class)->record($this->company->id,
            $this->dxb->id, $this->dxb->id, 10, 'AED', 'x');
    }

    public function test_settle_posts_balanced_intercompany_journal(): void
    {
        $svc = app(InterBranchService::class);
        $e1 = $svc->record($this->company->id, $this->dxb->id, $this->tz->id,
            150.0, 'AED', 'Passenger fee', 'cargo_trip', 1);
        $e2 = $svc->record($this->company->id, $this->dxb->id, $this->tz->id,
            40.0, 'AED', 'Packing', 'manual', null);

        $journals = $svc->settle($this->company->id, [$e1->id, $e2->id], $this->finance->id);
        $this->assertCount(1, $journals);

        $j = $journals[0];
        $this->assertSame('posted', $j->status);
        $this->assertSame(380.0, (float) $j->lines->sum('debit'));   // receivable + handling charge
        $this->assertSame(380.0, (float) $j->lines->sum('credit'));  // recovery + payable
        $this->assertSame(4, $j->lines->count());

        // Branch attribution on each leg.
        $byBranch = $j->lines->groupBy('branch_id');
        $this->assertSame(190.0, (float) $byBranch[$this->dxb->id]->sum('debit'));   // receivable
        $this->assertSame(190.0, (float) $byBranch[$this->dxb->id]->sum('credit'));  // recovery
        $this->assertSame(190.0, (float) $byBranch[$this->tz->id]->sum('debit'));    // handling charge
        $this->assertSame(190.0, (float) $byBranch[$this->tz->id]->sum('credit'));   // payable

        $this->assertSame('settled', $e1->fresh()->status);
        $this->assertSame($j->id, $e1->fresh()->journal_id);

        // GL entries exist for both branches.
        $this->assertDatabaseHas('general_ledger_entries',
            ['journal_id' => $j->id, 'branch_id' => $this->dxb->id]);
        $this->assertDatabaseHas('general_ledger_entries',
            ['journal_id' => $j->id, 'branch_id' => $this->tz->id]);

        // Intercompany COA auto-created.
        $this->assertNotNull(ChartOfAccount::where('company_id', $this->company->id)
            ->where('code', '1400')->first());
        $this->assertNotNull(ChartOfAccount::where('company_id', $this->company->id)
            ->where('code', '2300')->first());
    }

    public function test_settled_entries_excluded_from_next_reconciliation_open_total(): void
    {
        $svc = app(InterBranchService::class);
        $e = $svc->record($this->company->id, $this->dxb->id, $this->tz->id, 100, 'USD', 'x');
        $svc->settle($this->company->id, [$e->id], $this->finance->id);

        $rec = $svc->reconciliation($this->company->id, now()->year, now()->month);
        $this->assertSame(0.0, $rec['pairs']->first()['open_total']);
        $this->assertSame(100.0, $rec['pairs']->first()['settled_total']);
    }
}
