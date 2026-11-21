<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Employee\Models\JobPosition;
use Modules\Identity\Services\AssignmentService;
use Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder;
use Modules\Organization\Enums\EntityStatus;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\BranchAdministrator;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;
use Modules\Organization\Services\OrganizationService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrganizationPhase1Test extends TestCase
{
    use RefreshDatabase;

    private User $groupAdmin;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(OrganizationDatabaseSeeder::class);

        $this->company = Company::where('code', 'XG')->first();
        $this->groupAdmin = User::factory()->create(['status' => 'active']);
        $this->groupAdmin->assignRole(
            tap(Role::firstOrCreate(['name' => 'CEO and Management', 'guard_name' => 'web']),
                fn ($r) => $r->givePermissionTo(OrganizationDatabaseSeeder::GROUP_ADMIN_PERMISSIONS))
        );
    }

    /* ------------------------- Seeding ------------------------- */

    public function test_default_branches_seeded(): void
    {
        $dxb = Branch::where('code', 'DXB')->first();
        $tz = Branch::where('code', 'TZ')->first();

        $this->assertNotNull($dxb);
        $this->assertSame('AED', $dxb->currency);
        $this->assertSame('Asia/Dubai', $dxb->timezone);
        $this->assertSame('AE', $dxb->country_code);
        $this->assertEquals(EntityStatus::Active, $dxb->status);

        $this->assertNotNull($tz);
        $this->assertSame('TZS', $tz->currency);
        $this->assertSame('Africa/Dar_es_Salaam', $tz->timezone);
    }

    public function test_seeding_is_idempotent(): void
    {
        $this->seed(OrganizationDatabaseSeeder::class);
        $this->seed(OrganizationDatabaseSeeder::class);

        $this->assertSame(1, Branch::where('code', 'DXB')->count());
        $this->assertSame(1, Branch::where('code', 'TZ')->count());
        $this->assertSame(1, Company::where('code', 'XG')->count());
    }

    /* ------------------------- Branch ops ------------------------- */

    public function test_group_admin_can_create_branch(): void
    {
        $branch = app(OrganizationService::class)->createBranch([
            'company_id' => $this->company->id,
            'name' => 'Nairobi Office', 'code' => 'NBO', 'branch_type' => 'branch',
            'country' => 'Kenya', 'country_code' => 'KE',
            'currency' => 'KES', 'timezone' => 'Africa/Nairobi',
        ], $this->groupAdmin->id);

        $this->assertDatabaseHas('branches', ['code' => 'NBO', 'currency' => 'KES']);
    }

    public function test_branch_code_globally_unique(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        Branch::create(['code' => 'DXB', 'name' => 'Dup', 'company_id' => $this->company->id]);
    }

    public function test_activate_deactivate_records_timestamps(): void
    {
        $service = app(OrganizationService::class);
        $branch = Branch::where('code', 'DXB')->first();

        $service->deactivateBranch($branch);
        $this->assertEquals(EntityStatus::Suspended, $branch->fresh()->status);
        $this->assertNotNull($branch->fresh()->deactivated_at);

        $service->activateBranch($branch);
        $this->assertEquals(EntityStatus::Active, $branch->fresh()->status);
        $this->assertNull($branch->fresh()->deactivated_at);
        $this->assertNotNull($branch->fresh()->activated_at);
    }

    public function test_deactivation_is_not_destructive(): void
    {
        $branch = Branch::where('code', 'DXB')->first();
        app(OrganizationService::class)->deactivateBranch($branch);

        $this->assertDatabaseHas('branches', ['id' => $branch->id]); // soft — row kept
    }

    /* ------------------------- Departments ------------------------- */

    public function test_department_code_unique_per_branch(): void
    {
        $dxb = Branch::where('code', 'DXB')->first();
        Department::create(['company_id' => $this->company->id, 'branch_id' => $dxb->id,
                            'code' => 'IT', 'name' => 'IT Dubai', 'status' => 'active']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Department::create(['company_id' => $this->company->id, 'branch_id' => $dxb->id,
                            'code' => 'IT', 'name' => 'IT duplicate', 'status' => 'active']);
    }

    public function test_same_department_code_allowed_in_other_branch(): void
    {
        $dxb = Branch::where('code', 'DXB')->first();
        $tz = Branch::where('code', 'TZ')->first();
        Department::create(['company_id' => $this->company->id, 'branch_id' => $dxb->id,
                            'code' => 'IT', 'name' => 'IT Dubai', 'status' => 'active']);
        Department::create(['company_id' => $this->company->id, 'branch_id' => $tz->id,
                            'code' => 'IT', 'name' => 'IT Tanzania', 'status' => 'active']);

        $this->assertSame(2, Department::where('code', 'IT')->count());
    }

    /* ------------------------- Positions ------------------------- */

    public function test_positions_use_job_positions_table(): void
    {
        JobPosition::create(['code' => 'GM', 'title' => 'Group Manager',
                             'company_id' => $this->company->id, 'status' => 'active']);

        $this->assertDatabaseHas('job_positions', ['code' => 'GM']);
    }

    /* ------------------------- Authorization ------------------------- */

    public function test_non_privileged_user_cannot_manage_branches(): void
    {
        $plain = User::factory()->create(['status' => 'active']);
        $branch = Branch::where('code', 'DXB')->first();

        $this->assertFalse($plain->can('organization.branches.create'));
        $this->actingAs($plain)->post("/organization/branches/{$branch->id}/deactivate")->assertForbidden();
        $this->actingAs($plain)->post('/organization/branches', [])->assertForbidden();
    }

    public function test_group_admin_can_manage_branches_via_web(): void
    {
        $branch = Branch::where('code', 'DXB')->first();

        $this->actingAs($this->groupAdmin)
            ->post("/organization/branches/{$branch->id}/deactivate")
            ->assertRedirect();
        $this->assertEquals(EntityStatus::Suspended, $branch->fresh()->status);

        $this->actingAs($this->groupAdmin)
            ->post("/organization/branches/{$branch->id}/activate")
            ->assertRedirect();
        $this->assertEquals(EntityStatus::Active, $branch->fresh()->status);
    }

    public function test_branch_scoped_user_sees_only_own_branch(): void
    {
        $tz = Branch::where('code', 'TZ')->first();
        $scoped = User::factory()->create(['status' => 'active', 'branch_id' => $tz->id]);
        $scoped->givePermissionTo('organization.branches.view');

        $this->assertTrue(
            app(\Modules\Organization\Policies\BranchPolicy::class)->view($scoped, $tz)
        );
        $this->assertFalse(
            app(\Modules\Organization\Policies\BranchPolicy::class)->view(
                $scoped, Branch::where('code', 'DXB')->first())
        );
    }

    public function test_administrator_assignment_scopes_visibility(): void
    {
        $dxb = Branch::where('code', 'DXB')->first();
        $admin = User::factory()->create(['status' => 'active']);
        $admin->givePermissionTo('organization.branches.view');

        // Before assignment: cannot see DXB (no scope entries)
        $this->assertFalse(
            app(\Modules\Organization\Policies\BranchPolicy::class)->view($admin, $dxb)
        );

        app(OrganizationService::class)->assignBranchAdministrator($dxb, $admin->id, $this->groupAdmin->id);

        $this->assertTrue(
            app(\Modules\Organization\Policies\BranchPolicy::class)->view($admin->fresh(), $dxb)
        );
    }

    /* ------------------------- Deactivated branch rejects assignments ---- */

    public function test_suspended_branch_rejects_new_assignments(): void
    {
        $dxb = Branch::where('code', 'DXB')->first();
        app(OrganizationService::class)->deactivateBranch($dxb);

        $this->expectException(ValidationException::class);
        app(AssignmentService::class)->validatePlacement($this->company->id, $dxb->id, null);
    }

    /* ------------------------- Branch settings ------------------------- */

    public function test_branch_settings_roundtrip(): void
    {
        $service = app(OrganizationService::class);
        $dxb = Branch::where('code', 'DXB')->first();

        $service->setBranchSetting($dxb, 'working_days', ['sun', 'mon', 'tue', 'wed', 'thu'], 1, 'json');
        $service->setBranchSetting($dxb, 'business_open', '09:00', 1);

        $settings = $service->branchSettings($dxb->fresh());
        $this->assertSame(['sun', 'mon', 'tue', 'wed', 'thu'], $settings['working_days']);
        $this->assertSame('09:00', $settings['business_open']);
    }

    /* ------------------------- API ------------------------- */

    public function test_api_branch_lifecycle(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->groupAdmin);
        $branch = Branch::where('code', 'DXB')->first();

        $this->postJson("/api/v1/organization/branches/{$branch->id}/deactivate")
            ->assertOk();
        $this->assertEquals(EntityStatus::Suspended, $branch->fresh()->status);

        $this->postJson("/api/v1/organization/branches/{$branch->id}/activate")
            ->assertOk()->assertJsonPath('data.status', 'active');
    }

    public function test_api_requires_permission(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs(User::factory()->create(['status' => 'active']));
        $branch = Branch::where('code', 'DXB')->first();

        $this->postJson("/api/v1/organization/branches/{$branch->id}/deactivate")->assertForbidden();
        $this->getJson('/api/v1/organization/branches')->assertForbidden();
    }

    public function test_api_administrator_assignment(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->groupAdmin);
        $branch = Branch::where('code', 'DXB')->first();
        $target = User::factory()->create(['status' => 'active']);

        $this->postJson("/api/v1/organization/branches/{$branch->id}/administrators",
            ['user_id' => $target->id])->assertCreated();

        $this->assertTrue(
            BranchAdministrator::where('branch_id', $branch->id)
                ->where('user_id', $target->id)->active()->exists()
        );
    }
}
