<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\User;
use App\Services\Executive\ExecutiveAnalyticsService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Commercial\Models\Customer;
use Modules\Employee\Database\Seeders\EmployeeDatabaseSeeder;
use Modules\Finance\Database\Seeders\FinanceDatabaseSeeder;
use Modules\Finance\Models\ChartOfAccount;
use Modules\Finance\Models\FinancialPeriod;
use Modules\Finance\Services\JournalService;
use Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Tests\TestCase;

class ExecutiveCommandCenterTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $dxb;
    private User $admin;

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
        $this->dxb = Branch::where('code', 'DXB')->first()
            ?? Branch::firstOrCreate(['company_id' => $this->company->id, 'code' => 'DXB'], ['name' => 'Dubai', 'status' => 'active']);
        $this->admin = User::factory()->create(['status' => AccountStatus::Active]);
        $this->admin->assignRole('super-admin');
    }

    private function postRevenue(float $amount, ?int $branchId = null): void
    {
        $period = FinancialPeriod::where('company_id', $this->company->id)->where('status', 'open')->firstOrFail();
        $rev = ChartOfAccount::where('company_id', $this->company->id)->where('code', '4100')->firstOrFail();
        $cash = ChartOfAccount::where('company_id', $this->company->id)->where('code', '1200')->firstOrFail();
        app(JournalService::class)->post(app(JournalService::class)->create([
            'company_id' => $this->company->id, 'period_id' => $period->id,
            'type' => 'sales', 'journal_date' => $period->starts_on->toDateString(), 'memo' => 'test revenue',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => $amount, 'credit' => 0, 'branch_id' => $branchId],
                ['account_id' => $rev->id, 'debit' => 0, 'credit' => $amount, 'branch_id' => $branchId],
            ],
        ], $this->admin->id), $this->admin->id);
    }

    public function test_unauthorized_user_blocked(): void
    {
        $staff = User::factory()->create(['status' => AccountStatus::Active]);
        $staff->assignRole('staff');
        $this->actingAs($staff)->get('/executive')->assertForbidden();
    }

    public function test_dashboard_renders_for_super_admin(): void
    {
        $this->actingAs($this->admin)->get('/executive')
            ->assertOk()->assertSee('Executive Command Center')->assertSee('Branch Performance');
    }

    public function test_kpis_trace_to_gl_and_branch_scope_works(): void
    {
        $this->postRevenue(1000.0, $this->dxb->id);
        $this->postRevenue(500.0, null);

        $an = app(ExecutiveAnalyticsService::class);
        [$from, $to, $pf, $pt] = $an->period('year', null, null);

        $all = $an->executive($this->company->id, $from, $to, $pf, $pt, null);
        $this->assertEquals(1500.0, $all['revenue']['value']);

        $dxb = $an->executive($this->company->id, $from, $to, $pf, $pt, $this->dxb->id);
        $this->assertEquals(1000.0, $dxb['revenue']['value']);

        $rows = $an->branches($this->company->id, $from, $to);
        $this->assertEquals(1000.0, (float) $rows->firstWhere('branch.id', $this->dxb->id)['revenue']);
    }

    public function test_api_dashboard_returns_same_aggregation(): void
    {
        $this->postRevenue(250.0);
        Sanctum::actingAs($this->admin);
        $res = $this->getJson('/api/v1/executive/dashboard?range=year');
        $res->assertOk()->assertJsonPath('kpis.revenue.value', 250);
    }

    public function test_api_denied_without_permission(): void
    {
        $staff = User::factory()->create(['status' => AccountStatus::Active]);
        $staff->assignRole('staff');
        Sanctum::actingAs($staff);
        $this->getJson('/api/v1/executive/dashboard')->assertForbidden();
    }

    public function test_empty_dataset_renders_zeros_not_errors(): void
    {
        $an = app(ExecutiveAnalyticsService::class);
        [$from, $to, $pf, $pt] = $an->period('last30', null, null);
        $k = $an->executive($this->company->id, $from, $to, $pf, $pt, null);
        $this->assertEquals(0.0, $k['revenue']['value']);
        $this->assertEquals(0.0, $k['net_profit']['value']);
    }
}
