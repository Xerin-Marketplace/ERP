<?php

namespace Modules\Employee\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Employee\Database\Seeders\EmployeeDatabaseSeeder;
use Modules\Employee\Models\ChangeRequest;
use Modules\Employee\Models\DocumentCategory;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\EmploymentRecord;
use Modules\Employee\Models\JobGrade;
use Modules\Employee\Models\JobPosition;
use Modules\Employee\Models\OffboardingRecord;
use Modules\Employee\Models\ReportingRelationship;
use Modules\Employee\Models\TransferRequest;
use Modules\Employee\Services\ChangeService;
use Modules\Employee\Services\DocumentService;
use Modules\Employee\Services\EmployeeService;
use Modules\Employee\Services\OffboardingService;
use Modules\Employee\Services\OnboardingService;
use Modules\Employee\Services\ReportingService;
use Modules\Employee\Services\TransferService;
use Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;
use Tests\TestCase;

class EmployeeModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(OrganizationDatabaseSeeder::class);
        $this->seed(IdentityDatabaseSeeder::class);
        $this->seed(EmployeeDatabaseSeeder::class);
    }

    private function admin(): User
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole('super-admin');
        return $u;
    }

    private function company(): Company
    {
        return Company::where('code', 'XG')->firstOrFail();
    }

    private function branch(): Branch
    {
        return Branch::firstOrCreate(
            ['company_id' => $this->company()->id, 'code' => 'HQ'],
            ['name' => 'HQ Branch', 'status' => 'active']
        );
    }

    private function department(): Department
    {
        return Department::firstOrCreate(
            ['company_id' => $this->company()->id, 'code' => 'IT'],
            ['name' => 'IT Dept', 'status' => 'active', 'branch_id' => $this->branch()->id]
        );
    }

    private function makeEmployee(array $overrides = [], ?User $user = null): Employee
    {
        return app(EmployeeService::class)->create(array_merge([
            'first_name' => 'John',
            'last_name' => 'Mwangi',
            'employment_type' => 'permanent',
            'user_id' => $user?->id,
            'company_id' => $this->company()->id,
            'branch_id' => $this->branch()->id,
            'department_id' => $this->department()->id,
        ], $overrides), $user?->id ?? $this->admin()->id);
    }

    /* ------------------- Creation & numbering ------------------- */

    public function test_employee_creation_assigns_unique_sequential_number(): void
    {
        $e1 = $this->makeEmployee();
        $e2 = $this->makeEmployee();

        $code = $this->branch()->code;
        $this->assertMatchesRegularExpression("/^EMP-{$code}-\d{6}$/", $e1->employee_number);
        $this->assertNotEquals($e1->employee_number, $e2->employee_number);
        $this->assertStringEndsWith('000001', $e1->employee_number);
        $this->assertStringEndsWith('000002', $e2->employee_number);
    }

    public function test_employee_number_never_regenerated_on_update(): void
    {
        $e = $this->makeEmployee();
        $num = $e->employee_number;
        app(EmployeeService::class)->update($e, ['official_email' => 'j@x.com'], $this->admin()->id);
        $this->assertSame($num, $e->fresh()->employee_number);
    }

    public function test_placement_rejects_branch_from_other_company(): void
    {
        $other = Company::create(['code' => 'OT', 'legal_name' => 'Other Co', 'status' => 'active']);
        $foreignBranch = Branch::create(['company_id' => $other->id, 'code' => 'FB', 'name' => 'Foreign', 'status' => 'active']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->makeEmployee(['company_id' => $this->company()->id, 'branch_id' => $foreignBranch->id]);
    }

    public function test_duplicate_user_link_rejected(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->makeEmployee([], $user);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Employee::create(['employee_number' => 'XERIN-EMP-2099-999999', 'user_id' => $user->id]);
    }

    /* ------------------- Access scoping ------------------- */

    public function test_directory_scoped_for_non_privileged_viewer(): void
    {
        $viewerUser = User::factory()->create(['status' => 'active']);
        $viewerUser->assignRole('branch-manager');
        $viewerEmp = $this->makeEmployee([], $viewerUser);

        // Employee in a different company
        $other = Company::create(['code' => 'OC', 'legal_name' => 'Other', 'status' => 'active']);
        $ob = Branch::create(['company_id' => $other->id, 'code' => 'OB', 'name' => 'OB', 'status' => 'active']);
        $outsider = $this->makeEmployee(['company_id' => $other->id, 'branch_id' => $ob->id, 'department_id' => null]);

        $list = app(EmployeeService::class)->directory($viewerUser, [])->get();
        $this->assertTrue($list->contains('id', $viewerEmp->id));
        $this->assertFalse($list->contains('id', $outsider->id));
    }

    public function test_view_all_sees_everything(): void
    {
        $other = Company::create(['code' => 'OC2', 'legal_name' => 'Other2', 'status' => 'active']);
        $outsider = $this->makeEmployee(['company_id' => $other->id, 'branch_id' => null, 'department_id' => null]);

        $list = app(EmployeeService::class)->directory($this->admin(), [])->get();
        $this->assertTrue($list->contains('id', $outsider->id));
    }

    public function test_unprivileged_web_access_denied(): void
    {
        $u = User::factory()->create(['status' => 'active']); // no role → no perms
        $this->actingAs($u)->get('/employee')->assertForbidden();
        $this->actingAs($u)->get('/employee/dashboard')->assertForbidden();
    }

    /* ------------------- Structure ------------------- */

    public function test_position_department_must_match_company(): void
    {
        $other = Company::create(['code' => 'OC3', 'legal_name' => 'Other3', 'status' => 'active']);
        $foreignDept = Department::create(['company_id' => $other->id, 'code' => 'FD', 'name' => 'FD', 'status' => 'active']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\Modules\Employee\Services\StructureService::class)->savePosition([
            'code' => 'P1', 'title' => 'P1', 'company_id' => $this->company()->id,
            'department_id' => $foreignDept->id,
        ]);
    }

    public function test_position_cannot_report_to_itself(): void
    {
        $p = JobPosition::create(['code' => 'PS1', 'title' => 'PS1', 'company_id' => $this->company()->id]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\Modules\Employee\Services\StructureService::class)->savePosition(
            ['code' => 'PS1', 'title' => 'PS1', 'company_id' => $this->company()->id, 'reports_to_position_id' => $p->id],
            $p
        );
    }

    /* ------------------- Reporting ------------------- */

    public function test_circular_reporting_prevented(): void
    {
        $a = $this->makeEmployee();
        $b = $this->makeEmployee();
        $svc = app(ReportingService::class);

        $svc->assignManager($b, $a, 'direct', $this->admin()->id);
        $this->assertSame($a->id, $b->fresh()->manager_employee_id);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $svc->assignManager($a, $b, 'direct', $this->admin()->id); // a → b → a cycle
    }

    public function test_manager_change_preserves_history(): void
    {
        $a = $this->makeEmployee();
        $m1 = $this->makeEmployee();
        $m2 = $this->makeEmployee();
        $svc = app(ReportingService::class);

        $svc->assignManager($a, $m1, 'direct', $this->admin()->id);
        $svc->assignManager($a, $m2, 'direct', $this->admin()->id);

        $rels = $a->reportingRelationships()->where('type', 'direct')->get();
        $this->assertCount(2, $rels);
        $this->assertCount(1, $rels->whereNull('effective_end'));
        $this->assertSame($m2->id, $a->fresh()->manager_employee_id);
    }

    /* ------------------- Documents ------------------- */

    public function test_document_upload_versions_and_download_auth(): void
    {
        Storage::fake('local');
        $e = $this->makeEmployee();
        $cat = DocumentCategory::where('code', 'identification')->firstOrFail();
        $svc = app(DocumentService::class);

        $d1 = $svc->upload($e, ['category_id' => $cat->id, 'title' => 'Passport', 'expires_at' => '2030-01-01'],
            UploadedFile::fake()->create('pass.pdf', 100, 'application/pdf'), $this->admin()->id);
        $d2 = $svc->upload($e, ['category_id' => $cat->id, 'title' => 'Passport', 'expires_at' => '2031-01-01'],
            UploadedFile::fake()->create('pass2.pdf', 100, 'application/pdf'), $this->admin()->id);

        $this->assertSame(1, $d1->version);
        $this->assertSame(2, $d2->version);

        // Unauthorized user cannot download
        $stranger = User::factory()->create(['status' => 'active']);
        $this->actingAs($stranger)->get("/employee/documents/{$d1->id}/download")->assertForbidden();

        // Owner's own download works when self-permission held
        $owner = User::factory()->create(['status' => 'active']);
        $owner->assignRole('employee');
        $owned = $this->makeEmployee([], $owner);
        $ownDoc = $svc->upload($owned, ['category_id' => $cat->id, 'title' => 'ID', 'expires_at' => '2030-01-01'],
            UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'), $this->admin()->id);
        $this->actingAs($owner)->get("/employee/documents/{$ownDoc->id}/download")->assertOk();
    }

    public function test_expiry_required_category_enforces_date(): void
    {
        Storage::fake('local');
        $e = $this->makeEmployee();
        $cat = DocumentCategory::where('code', 'identification')->firstOrFail(); // requires_expiry

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(DocumentService::class)->upload($e, ['category_id' => $cat->id, 'title' => 'No expiry'],
            UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'), $this->admin()->id);
    }

    /* ------------------- Transfers ------------------- */

    public function test_transfer_approval_updates_placement_and_iam_history(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $employee = $this->makeEmployee([], $user);
        $newDept = Department::create(['company_id' => $this->company()->id, 'code' => 'HR', 'name' => 'HR', 'status' => 'active', 'branch_id' => $this->branch()->id]);
        $approver = $this->admin();

        $t = app(TransferService::class)->request($employee, [
            'type' => 'department', 'to_department_id' => $newDept->id, 'reason' => 'Reorg',
        ], $approver);
        $this->assertSame('pending', $t->status);

        app(TransferService::class)->approve($t, $approver);

        $e = $employee->fresh();
        $this->assertSame($newDept->id, $e->department_id);
        $this->assertSame($this->branch()->id, $e->branch_id); // unchanged

        // IAM assignment history preserved: 1 ended + 1 active
        $assigns = $user->assignments()->get();
        $this->assertCount(1, $assigns->whereNull('effective_end'));
        $this->assertSame('transfer', $assigns->whereNull('effective_end')->first()->assignment_type);
    }

    public function test_transfer_reject_requires_note_and_keeps_placement(): void
    {
        $employee = $this->makeEmployee();
        $t = app(TransferService::class)->request($employee, ['type' => 'branch'], $this->admin());

        $approver = $this->admin();
        $this->actingAs($approver)->post("/employee/transfers/{$t->id}/reject", [])->assertSessionHasErrors('note');

        app(TransferService::class)->reject($t, $approver, 'Not justified');
        $this->assertSame('rejected', $t->fresh()->status);
        $this->assertSame($this->branch()->id, $employee->fresh()->branch_id);
    }

    /* ------------------- Changes ------------------- */

    public function test_promotion_applies_position_on_approval(): void
    {
        $employee = $this->makeEmployee();
        $pos = JobPosition::create(['code' => 'SM', 'title' => 'Senior Manager', 'company_id' => $this->company()->id]);
        $approver = $this->admin();

        $c = app(ChangeService::class)->request($employee, 'promotion', ['job_position_id' => $pos->id], $approver, 'Great work');
        app(ChangeService::class)->approve($c, $approver);

        $this->assertSame($pos->id, $employee->fresh()->job_position_id);
        $this->assertSame(['job_position_id' => null], $c->fresh()->previous_values);
    }

    public function test_contract_renewal_creates_new_record_and_marks_old(): void
    {
        $employee = $this->makeEmployee(['employment_type' => 'contract']);
        $old = $employee->employmentRecords()->create([
            'type' => 'contract', 'status' => 'active', 'start_date' => '2025-01-01', 'end_date' => '2026-01-01',
        ]);
        $approver = $this->admin();

        $c = app(ChangeService::class)->request($employee, 'contract_renewal', ['end_date' => '2027-01-01'], $approver);
        app(ChangeService::class)->approve($c, $approver);

        $this->assertSame('renewed', $old->fresh()->status);
        $this->assertSame('2027-01-01', $employee->fresh()->contract_end_date->toDateString());
        $this->assertCount(2, $employee->employmentRecords);
    }

    /* ------------------- Onboarding ------------------- */

    public function test_onboarding_completes_and_activates_employee(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $employee = $this->makeEmployee([], $user);
        $admin = $this->admin();

        $record = app(OnboardingService::class)->start($employee, $admin->id);
        $this->assertCount(8, $record->tasks);

        foreach ($record->tasks as $task) {
            app(OnboardingService::class)->updateTask($task, 'completed', $admin->id);
        }

        $this->assertSame('completed', $record->fresh()->status);
        $this->assertSame('active', $employee->fresh()->status);
    }

    /* ------------------- Offboarding ------------------- */

    public function test_offboarding_revokes_access_and_separates(): void
    {
        Notification::fake();
        $user = User::factory()->create(['status' => 'active']);
        $user->createToken('api')->plainTextToken;
        $employee = $this->makeEmployee([], $user);
        $admin = $this->admin();

        $rec = app(OffboardingService::class)->initiate($employee, [
            'reason' => 'resignation', 'separation_date' => now()->addDays(14)->toDateString(),
        ], $admin);
        app(OffboardingService::class)->approve($rec, $admin);
        app(OffboardingService::class)->complete($rec->fresh(), $admin);

        $this->assertSame('separated', $employee->fresh()->status);
        $this->assertSame('deactivated', $user->fresh()->status->value);
        $this->assertCount(0, $user->tokens);
        $this->assertNotNull($rec->fresh()->access_revoked_at);
    }

    public function test_double_offboarding_blocked(): void
    {
        $employee = $this->makeEmployee();
        $admin = $this->admin();
        app(OffboardingService::class)->initiate($employee, [
            'reason' => 'termination', 'separation_date' => now()->toDateString(),
        ], $admin);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(OffboardingService::class)->initiate($employee, [
            'reason' => 'termination', 'separation_date' => now()->toDateString(),
        ], $admin);
    }

    /* ------------------- Self-service ------------------- */

    public function test_self_service_updates_only_personal_fields(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('employee');
        $employee = $this->makeEmployee([], $user);

        $this->actingAs($user)->put('/employee/me', [
            'phone' => '+255711111111', 'preferred_name' => 'Johnny',
        ])->assertRedirect();

        $this->assertSame('+255711111111', $employee->fresh()->personal->phone);
        $this->assertSame($this->company()->id, $employee->fresh()->company_id); // unchanged
    }

    /* ------------------- API ------------------- */

    public function test_api_directory_and_envelope(): void
    {
        $this->makeEmployee();
        Sanctum::actingAs($this->admin());

        $res = $this->getJson('/api/v1/employees')->assertOk()->assertJson(['success' => true]);
        $this->assertNotEmpty($res->json('data.data'));
    }

    public function test_api_unauthenticated_denied(): void
    {
        $this->getJson('/api/v1/employees')->assertUnauthorized();
    }

    public function test_api_create_and_offboard(): void
    {
        Sanctum::actingAs($this->admin());

        $res = $this->postJson('/api/v1/employees', [
            'first_name' => 'API', 'last_name' => 'Created', 'employment_type' => 'permanent',
            'company_id' => $this->company()->id,
        ])->assertCreated()->assertJson(['success' => true]);

        $id = $res->json('data.id');
        $this->postJson("/api/v1/employees/{$id}/offboarding", [
            'reason' => 'contract_expiry', 'separation_date' => now()->toDateString(),
        ])->assertCreated();
    }

    public function test_api_regular_employee_cannot_list_directory(): void
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole('employee');
        Sanctum::actingAs($u);

        $this->getJson('/api/v1/employees')->assertForbidden();
    }

    /* ------------------- Dashboard & audit ------------------- */

    public function test_dashboard_metrics_use_real_data(): void
    {
        $this->makeEmployee(['status' => 'active']);
        $this->makeEmployee(['employment_type' => 'probation']);

        $admin = $this->admin();
        $metrics = app(EmployeeService::class)->dashboard($admin);

        $this->assertSame(2, $metrics['total']);
        $this->assertSame(1, $metrics['active']);
        $this->assertSame(1, $metrics['probation']);
        $this->assertNotEmpty($metrics['by_company']);
    }

    public function test_events_and_audit_recorded(): void
    {
        $employee = $this->makeEmployee();

        $this->assertDatabaseHas('employee_events', ['employee_id' => $employee->id, 'type' => 'employee.created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'employee.created', 'resource_id' => $employee->id]);
    }

    public function test_seeded_grades_and_categories_exist(): void
    {
        $this->assertCount(9, JobGrade::all());
        $this->assertCount(11, DocumentCategory::all());
        $this->assertTrue(DocumentCategory::where('code', 'employment_contract')->first()->is_confidential);
    }

    /* ------------------- Phase 3: branch-scoped numbering ------------------- */

    public function test_employee_numbers_are_scoped_per_branch(): void
    {
        $dxb = Branch::firstOrCreate(
            ['company_id' => $this->company()->id, 'code' => 'DXB'],
            ['name' => 'Dubai', 'status' => 'active']
        );

        $hqEmployee = $this->makeEmployee();
        $dxbEmployee = $this->makeEmployee(['branch_id' => $dxb->id, 'department_id' => null]);

        $this->assertStringStartsWith('EMP-HQ-', $hqEmployee->employee_number);
        $this->assertStringStartsWith('EMP-DXB-', $dxbEmployee->employee_number);
        $this->assertStringEndsWith('000001', $dxbEmployee->employee_number); // own sequence
        $this->assertNotEquals($hqEmployee->employee_number, $dxbEmployee->employee_number);
    }

    public function test_employee_number_is_generated_server_side_not_from_input(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('employee.store'), [
            'first_name' => 'A', 'last_name' => 'B',
            'employment_type' => 'permanent',
            'company_id' => $this->company()->id,
            'branch_id' => $this->branch()->id,
            'employee_number' => 'HACKED-999', // must be ignored
        ]);

        $this->assertDatabaseMissing('employees', ['employee_number' => 'HACKED-999']);
    }

    /* ------------------- Phase 3: lifecycle transitions ------------------- */

    public function test_status_transition_is_audited_with_reason_and_actor(): void
    {
        $admin = $this->admin();
        $admin->givePermissionTo('employee.suspend');
        $e = $this->makeEmployee(['status' => 'active'], $admin);

        $this->actingAs($admin)->post(route('employee.lifecycle.transition', $e), [
            'status' => 'suspended',
            'reason' => 'Disciplinary hold',
        ])->assertSessionHas('success');

        $e->refresh();
        $this->assertSame('suspended', $e->status);
        $this->assertDatabaseHas('employee_events', [
            'employee_id' => $e->id,
            'type' => 'status_changed',
            'actor_user_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'employee.status_changed']);
    }

    public function test_suspend_requires_suspend_permission(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('employee'); // only profile.update_own
        $e = $this->makeEmployee();

        $this->actingAs($user)->post(route('employee.lifecycle.transition', $e), [
            'status' => 'suspended', 'reason' => 'x',
        ])->assertForbidden();
    }

    /* ------------------- Phase 3: sensitive-field encryption ------------------- */

    public function test_national_id_is_encrypted_at_rest(): void
    {
        $e = $this->makeEmployee();
        app(EmployeeService::class)->update($e, ['national_id' => 'P1234567'], $this->admin()->id);

        $raw = \Illuminate\Support\Facades\DB::table('employee_personal_details')
            ->where('employee_id', $e->id)->value('national_id');
        $this->assertNotSame('P1234567', $raw);
        $this->assertSame('P1234567', $e->fresh()->personal->national_id); // decrypts on read
    }
}
