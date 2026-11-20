<?php

namespace Modules\Organization\Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder;
use Modules\Organization\Enums\EntityStatus;
use Modules\Organization\Events\CompanyCreated;
use Modules\Organization\Events\DepartmentTransferred;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;
use Modules\Organization\Models\DepartmentDefinition;
use Modules\Organization\Models\Location;
use Modules\Organization\Services\OrganizationService;
use Tests\TestCase;

class OrganizationModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(OrganizationDatabaseSeeder::class);
        // Mirror the production HQ branch (seeded by DatabaseSeeder on real installs)
        Branch::firstOrCreate(['code' => 'HQ'], [
            'name' => 'Head Office', 'branch_type' => 'head_office',
            'company_id' => Company::where('code', 'XG')->value('id'),
        ]);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('super-admin');
        return $user;
    }

    private function plainUser(): User
    {
        return User::factory()->create(['status' => 'active']);
    }

    private function group(): Company
    {
        return Company::where('code', 'XG')->firstOrFail();
    }

    /* --------------------------- Companies --------------------------- */

    public function test_company_can_be_created(): void
    {
        $company = app(OrganizationService::class)->createCompany([
            'code' => 'XSUB', 'legal_name' => 'Xerin Subsidiary Ltd',
            'company_type' => 'subsidiary', 'parent_company_id' => $this->group()->id,
        ], $this->admin()->id);

        $this->assertDatabaseHas('companies', ['code' => 'XSUB', 'legal_name' => 'Xerin Subsidiary Ltd']);
        $this->assertSame($this->group()->id, $company->parent_company_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'company.created', 'resource_id' => $company->id]);
    }

    public function test_parent_child_company_relationship_works(): void
    {
        $parent = $this->group();
        $child = Company::create(['code' => 'XC1', 'legal_name' => 'Child Co', 'company_type' => 'subsidiary', 'parent_company_id' => $parent->id]);

        $this->assertTrue($parent->subsidiaries->contains($child));
        $this->assertTrue($child->parent->is($parent));
    }

    public function test_company_cannot_be_its_own_parent(): void
    {
        $company = $this->group();
        $service = app(OrganizationService::class);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->assertCompanyParentValid($company, $company->id);
    }

    public function test_circular_company_relationship_is_prevented(): void
    {
        $group = $this->group();
        $child = Company::create(['code' => 'C2', 'legal_name' => 'Child', 'company_type' => 'subsidiary', 'parent_company_id' => $group->id]);
        $grandchild = Company::create(['code' => 'C3', 'legal_name' => 'Grandchild', 'company_type' => 'subsidiary', 'parent_company_id' => $child->id]);

        $service = app(OrganizationService::class);

        // Making the group a child of its own grandchild would create a cycle
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->assertCompanyParentValid($group, $grandchild->id);
    }

    public function test_duplicate_company_code_is_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/organization/companies', [
                'code' => 'XG', 'legal_name' => 'Duplicate Code Co', 'company_type' => 'entity',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_company_status_transitions_are_audited(): void
    {
        $admin = $this->admin();
        $company = $this->group();

        $this->actingAs($admin)
            ->post("/organization/companies/{$company->id}/status", ['status' => 'suspended'])
            ->assertRedirect();

        $this->assertSame(EntityStatus::Suspended, $company->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'company.suspended', 'resource_id' => $company->id]);
    }

    public function test_company_events_are_dispatched(): void
    {
        Event::fake([CompanyCreated::class]);

        app(OrganizationService::class)->createCompany([
            'code' => 'EV1', 'legal_name' => 'Event Co', 'company_type' => 'entity',
        ], $this->admin()->id);

        Event::assertDispatched(CompanyCreated::class);
    }

    /* ---------------------------- Branches ---------------------------- */

    public function test_branch_requires_valid_company(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/organization/branches', [
                'name' => 'Orphan', 'code' => 'ORPH', 'branch_type' => 'branch',
                'company_id' => 999,
            ])
            ->assertSessionHasErrors('company_id');
    }

    public function test_branch_can_be_created_and_belongs_to_company(): void
    {
        $admin = $this->admin();
        $company = $this->group();

        $response = $this->actingAs($admin)
            ->post('/organization/branches', [
                'name' => 'Arusha Branch', 'code' => 'ARU', 'branch_type' => 'branch',
                'company_id' => $company->id, 'country' => 'Tanzania', 'region' => 'Arusha',
            ]);

        $response->assertRedirect();
        $branch = Branch::where('code', 'ARU')->firstOrFail();
        $this->assertSame($company->id, $branch->company_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'branch.created']);
    }

    public function test_branches_are_never_hard_deleted(): void
    {
        $admin = $this->admin();
        $branch = Branch::create(['name' => 'Mwanza', 'code' => 'MWZ', 'branch_type' => 'branch', 'company_id' => $this->group()->id]);

        $this->actingAs($admin)
            ->post("/organization/branches/{$branch->id}/status", ['status' => 'archived']);

        $this->assertSame(EntityStatus::Archived, $branch->fresh()->status);
        $this->assertDatabaseHas('branches', ['id' => $branch->id]); // still exists
    }

    public function test_branch_index_filters_by_company_and_status(): void
    {
        $admin = $this->admin();
        $company = $this->group();
        Branch::create(['name' => 'Suspended One', 'code' => 'SUS', 'branch_type' => 'branch', 'company_id' => $company->id, 'status' => EntityStatus::Suspended]);

        $this->actingAs($admin)
            ->get('/organization/branches?status=suspended')
            ->assertOk()
            ->assertSee('Suspended One');
    }

    /* ---------------------------- Locations --------------------------- */

    public function test_location_can_be_created_for_company_and_branch(): void
    {
        $admin = $this->admin();
        $company = $this->group();
        $branch = Branch::where('code', 'HQ')->firstOrFail();

        $this->actingAs($admin)
            ->post('/organization/locations', [
                'name' => 'HQ Warehouse', 'code' => 'WH-HQ', 'location_type' => 'warehouse',
                'company_id' => $company->id, 'branch_id' => $branch->id,
                'city' => 'Dar es Salaam',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('locations', ['code' => 'WH-HQ', 'company_id' => $company->id, 'branch_id' => $branch->id]);
    }

    public function test_location_type_is_validated(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/organization/locations', [
                'name' => 'Bad Type', 'code' => 'BT', 'location_type' => 'invalid',
                'company_id' => $this->group()->id,
            ])
            ->assertSessionHasErrors('location_type');
    }

    /* --------------------------- Departments -------------------------- */

    public function test_department_definitions_are_seeded(): void
    {
        $this->assertGreaterThanOrEqual(11, DepartmentDefinition::count());
        $this->assertDatabaseHas('department_definitions', ['code' => 'IT']);
    }

    public function test_department_can_be_created_with_definition(): void
    {
        $admin = $this->admin();
        $def = DepartmentDefinition::where('code', 'FIN')->firstOrFail();

        $this->actingAs($admin)
            ->post('/organization/departments', [
                'department_definition_id' => $def->id,
                'code' => 'FIN-01', 'name' => 'Finance & Accounting',
                'company_id' => $this->group()->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('departments', [
            'code' => 'FIN-01', 'department_definition_id' => $def->id,
        ]);
    }

    public function test_same_definition_can_exist_across_branches(): void
    {
        $admin = $this->admin();
        $company = $this->group();
        $def = DepartmentDefinition::where('code', 'HR')->firstOrFail();
        $b2 = Branch::create(['name' => 'Dodoma', 'code' => 'DOD', 'branch_type' => 'branch', 'company_id' => $company->id]);

        app(OrganizationService::class)->createDepartment([
            'department_definition_id' => $def->id, 'code' => 'HR-HQ', 'name' => 'Human Resources',
            'company_id' => $company->id, 'branch_id' => Branch::where('code', 'HQ')->value('id'),
        ], $admin->id);

        app(OrganizationService::class)->createDepartment([
            'department_definition_id' => $def->id, 'code' => 'HR-DOD', 'name' => 'Human Resources',
            'company_id' => $company->id, 'branch_id' => $b2->id,
        ], $admin->id);

        $this->assertSame(2, Department::where('department_definition_id', $def->id)->count());
    }

    public function test_department_hierarchy_and_circular_prevention(): void
    {
        $admin = $this->admin();
        $service = app(OrganizationService::class);
        $company = $this->group();

        $parent = $service->createDepartment(['code' => 'P1', 'name' => 'Parent Dept', 'company_id' => $company->id], $admin->id);
        $child = $service->createDepartment(['code' => 'C1', 'name' => 'Child Dept', 'company_id' => $company->id, 'parent_id' => $parent->id], $admin->id);

        $this->assertTrue($parent->children->contains($child));

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->updateDepartment($parent, ['parent_id' => $child->id]); // cycle
    }

    public function test_department_transfer_dispatches_event_and_audits(): void
    {
        Event::fake([DepartmentTransferred::class]);
        $service = app(OrganizationService::class);
        $admin = $this->admin();
        $company = $this->group();

        $a = $service->createDepartment(['code' => 'A', 'name' => 'Dept A', 'company_id' => $company->id], $admin->id);
        $b = $service->createDepartment(['code' => 'B', 'name' => 'Dept B', 'company_id' => $company->id], $admin->id);
        $c = $service->createDepartment(['code' => 'CC', 'name' => 'Dept C', 'company_id' => $company->id, 'parent_id' => $a->id], $admin->id);

        $service->updateDepartment($c, ['parent_id' => $b->id]);

        Event::assertDispatched(DepartmentTransferred::class);
        $this->assertDatabaseHas('audit_logs', ['action' => 'department.transferred', 'resource_id' => $c->id]);
    }

    public function test_department_branch_must_belong_to_company(): void
    {
        $admin = $this->admin();
        $other = Company::create(['code' => 'OTH', 'legal_name' => 'Other Co', 'company_type' => 'entity']);
        $foreignBranch = Branch::create(['name' => 'Foreign', 'code' => 'FRN', 'branch_type' => 'branch', 'company_id' => $other->id]);

        $this->actingAs($admin)
            ->post('/organization/departments', [
                'code' => 'X1', 'name' => 'Mismatch',
                'company_id' => $this->group()->id, 'branch_id' => $foreignBranch->id,
            ])
            ->assertSessionHasErrors('branch_id');
    }

    public function test_suspended_branch_rejects_new_department_assignment(): void
    {
        $admin = $this->admin();
        $branch = Branch::create([
            'name' => 'Suspended Br', 'code' => 'SB1', 'branch_type' => 'branch',
            'company_id' => $this->group()->id, 'status' => EntityStatus::Suspended,
        ]);

        $this->actingAs($admin)
            ->post('/organization/departments', [
                'code' => 'D1', 'name' => 'Blocked',
                'company_id' => $this->group()->id, 'branch_id' => $branch->id,
            ])
            ->assertSessionHasErrors('branch_id');
    }

    /* -------------------------- Permissions --------------------------- */

    public function test_organization_pages_require_permission(): void
    {
        $user = $this->plainUser();

        $this->actingAs($user)->get('/organization')->assertStatus(403);
        $this->actingAs($user)->get('/organization/companies')->assertStatus(403);
        $this->actingAs($user)->get('/organization/branches')->assertStatus(403);
        $this->actingAs($user)->get('/organization/structure')->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_create_company_via_api(): void
    {
        Sanctum::actingAs($this->plainUser());

        $this->postJson('/api/v1/organization/companies', [
            'code' => 'NOPE', 'legal_name' => 'Nope', 'company_type' => 'entity',
        ])->assertStatus(403);
    }

    public function test_viewer_role_can_read_but_not_mutate(): void
    {
        $viewer = $this->plainUser();
        $viewer->assignRole('admin'); // org view permissions only

        $this->actingAs($viewer)->get('/organization')->assertOk();
        $this->actingAs($viewer)->post('/organization/companies', [
            'code' => 'XX', 'legal_name' => 'Blocked', 'company_type' => 'entity',
        ])->assertStatus(403);
    }

    /* ------------------------------- API ------------------------------ */

    public function test_api_companies_index_uses_standard_envelope(): void
    {
        Sanctum::actingAs($this->admin());

        $this->getJson('/api/v1/organization/companies')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['data' => [['id', 'code', 'legal_name', 'status']]]]);
    }

    public function test_api_company_crud(): void
    {
        Sanctum::actingAs($this->admin());

        $created = $this->postJson('/api/v1/organization/companies', [
            'code' => 'APIC', 'legal_name' => 'API Company', 'company_type' => 'entity',
        ]);
        $created->assertStatus(201)->assertJson(['success' => true]);
        $id = $created->json('data.id');

        $this->getJson("/api/v1/organization/companies/{$id}")->assertOk()
            ->assertJson(['data' => ['code' => 'APIC']]);

        $this->putJson("/api/v1/organization/companies/{$id}", ['trading_name' => 'API Trading'])
            ->assertOk();
        $this->assertDatabaseHas('companies', ['id' => $id, 'trading_name' => 'API Trading']);
    }

    public function test_api_search_and_pagination(): void
    {
        Sanctum::actingAs($this->admin());

        $this->getJson('/api/v1/organization/branches?search=Head&per_page=5')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.data.0.code', 'HQ');
    }

    public function test_api_structure_returns_hierarchy(): void
    {
        Sanctum::actingAs($this->admin());
        $company = $this->group();
        Department::create(['code' => 'OPS', 'name' => 'Operations', 'company_id' => $company->id]);

        $response = $this->getJson('/api/v1/organization/structure')->assertOk();
        $tree = $response->json('data');

        $this->assertSame('company', $tree[0]['type']);
        $this->assertSame('Xerin Group', $tree[0]['label']);
        $this->assertNotEmpty($tree[0]['children']);
    }

    public function test_api_overview_returns_real_counts(): void
    {
        Sanctum::actingAs($this->admin());
        $company = $this->group();
        Branch::create(['name' => 'Extra', 'code' => 'EXT', 'branch_type' => 'branch', 'company_id' => $company->id]);

        $data = $this->getJson('/api/v1/organization/overview')->assertOk()->json('data');

        $this->assertSame(1, $data['companies_total']);
        $this->assertSame(Branch::count(), $data['branches_total']);
        $this->assertSame(Department::count(), $data['departments_total']);
    }

    public function test_api_audit_logs_endpoint_is_permission_gated(): void
    {
        Sanctum::actingAs($this->plainUser());
        $this->getJson('/api/v1/organization/audit-logs')->assertStatus(403);

        Sanctum::actingAs($this->admin());
        $this->getJson('/api/v1/organization/audit-logs')->assertOk();
    }

    /* ---------------------------- Frontend ---------------------------- */

    public function test_overview_dashboard_shows_database_metrics(): void
    {
        $this->actingAs($this->admin())
            ->get('/organization')
            ->assertOk()
            ->assertSee('Xerin Group')
            ->assertSee('Head Office');
    }

    public function test_structure_page_renders_tree(): void
    {
        $this->actingAs($this->admin())
            ->get('/organization/structure')
            ->assertOk()
            ->assertSee('org-tree');
    }

    public function test_main_dashboard_uses_real_counts(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Companies')
            ->assertSee('Branches')
            ->assertDontSee('Total Revenue'); // fake metric removed
    }

    public function test_all_organization_pages_render(): void
    {
        $admin = $this->admin();
        $company = $this->group();
        $branch = Branch::where('code', 'HQ')->firstOrFail();
        $dept = Department::create(['code' => 'T1', 'name' => 'Test Dept', 'company_id' => $company->id]);

        $pages = [
            '/organization',
            '/organization/companies',
            '/organization/companies/create',
            "/organization/companies/{$company->id}",
            "/organization/companies/{$company->id}/edit",
            '/organization/branches',
            '/organization/branches/create',
            "/organization/branches/{$branch->id}",
            "/organization/branches/{$branch->id}/edit",
            '/organization/locations',
            '/organization/locations/create',
            '/organization/departments',
            '/organization/departments/create',
            "/organization/departments/{$dept->id}",
            "/organization/departments/{$dept->id}/edit",
            '/organization/structure',
            '/organization/audit',
            '/organization/settings',
        ];

        foreach ($pages as $page) {
            $this->actingAs($admin)->get($page)->assertOk()->assertDontSee('Exception');
        }
    }

    public function test_settings_can_be_saved(): void
    {
        $this->actingAs($this->admin())
            ->put('/organization/settings', [
                'default_currency' => 'usd',
                'default_timezone' => 'Africa/Nairobi',
                'default_country' => 'Kenya',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('system_settings', ['key' => 'organization.default_currency']);
    }

    public function test_audit_page_lists_organization_events(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/organization/companies', [
            'code' => 'AUD1', 'legal_name' => 'Audit Target', 'company_type' => 'entity',
        ]);

        $this->actingAs($admin)
            ->get('/organization/audit')
            ->assertOk()
            ->assertSee('company.created');
    }

    public function test_audit_log_page_shows_organization_events(): void
    {
        $admin = $this->admin();
        $company = $this->group();

        $this->actingAs($admin)
            ->post("/organization/branches", [
                'name' => 'Audited Branch', 'code' => 'AUD', 'branch_type' => 'branch', 'company_id' => $company->id,
            ]);

        $this->assertTrue(
            AuditLog::where('action', 'branch.created')->where('resource_id', Branch::where('code', 'AUD')->value('id'))->exists()
        );
    }
}
