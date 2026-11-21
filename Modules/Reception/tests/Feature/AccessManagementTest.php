<?php

namespace Modules\Reception\Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Employee\Database\Seeders\EmployeeDatabaseSeeder;
use Modules\Employee\Models\Employee;
use Modules\Employee\Services\EmployeeService;
use Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use Modules\Identity\Notifications\AccountActivationInvitation;
use Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;
use Modules\Reception\Database\Seeders\ReceptionDatabaseSeeder;
use Modules\Reception\Models\AccessApprovalTask;
use Modules\Reception\Models\AccessRequest;
use Modules\Reception\Models\ExternalUserProfile;
use Modules\Reception\Services\AccessProvisioningService;
use Modules\Reception\Services\AccessRequestService;
use Modules\Reception\Services\AccessWorkflowService;
use Modules\Reception\Services\ApprovalTaskService;
use Modules\Reception\Services\VerificationService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionist;
    private User $itAdmin;
    private User $deptManager;
    private User $hrAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(OrganizationDatabaseSeeder::class);
        $this->seed(IdentityDatabaseSeeder::class);
        $this->seed(EmployeeDatabaseSeeder::class);
        $this->seed(ReceptionDatabaseSeeder::class);

        $this->receptionist = $this->userWithRole('Customer Care');
        $this->itAdmin = $this->userWithRole('CTO and Admin');
        $this->deptManager = $this->userWithRole('Branch Manager');
        $this->hrAdmin = $this->userWithRole('CTO and Admin');
    }

    private function userWithRole(string $role): User
    {
        $u = User::factory()->create(['status' => AccountStatus::Active]);
        $u->assignRole($role);
        return $u;
    }

    private function company(): Company { return Company::where('code', 'XG')->firstOrFail(); }

    private function branch(): Branch
    {
        return Branch::firstOrCreate(
            ['company_id' => $this->company()->id, 'code' => 'TZ'],
            ['name' => 'Tanzania', 'status' => 'active']);
    }

    private function department(): Department
    {
        return Department::firstOrCreate(
            ['company_id' => $this->company()->id, 'code' => 'OPS-TZ'],
            ['name' => 'Ops TZ', 'status' => 'active', 'branch_id' => $this->branch()->id]);
    }

    private function employeeFor(User $user): Employee
    {
        return app(EmployeeService::class)->create([
            'first_name' => 'Test', 'last_name' => 'Employee', 'employment_type' => 'permanent',
            'user_id' => $user->id, 'company_id' => $this->company()->id,
            'branch_id' => $this->branch()->id, 'department_id' => $this->department()->id,
        ], $this->itAdmin->id);
    }

    private function submitRequest(array $overrides = []): AccessRequest
    {
        return app(AccessRequestService::class)->submit(array_merge([
            'type' => 'new_employee', 'full_name' => 'Asha Mrema',
            'email' => 'asha@example.com', 'phone' => '+255700000000',
            'branch_id' => $this->branch()->id, 'department_id' => $this->department()->id,
            'job_title' => 'Officer', 'justification' => 'New hire.',
        ], $overrides));
    }

    public function test_public_submission_creates_request_with_reference_and_token(): void
    {
        $r = $this->submitRequest();
        $this->assertSame('submitted', $r->status);
        $this->assertMatchesRegularExpression('/^XER-ACC-\d{4}-\d{6}$/', $r->reference);
        $this->assertNotEmpty($r->tracking_token);
        $this->assertDatabaseHas('access_request_history', ['request_id' => $r->id, 'event' => 'request.submitted']);
    }

    public function test_duplicate_active_submission_is_blocked(): void
    {
        $this->submitRequest();
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->submitRequest();
    }

    public function test_external_request_requires_sponsor_and_expiry(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->submitRequest(['type' => 'external', 'organization' => 'Acme Ltd', 'email' => 'ext@acme.com']);
    }

    public function test_workflow_resolution_and_routing_emits_stage_tasks(): void
    {
        $r = $this->submitRequest();
        app(VerificationService::class)->bootstrap($r);
        $version = app(AccessWorkflowService::class)->resolve($r);
        $this->assertNotNull($version);
        $this->assertSame('New Employee Access', $version->workflow->name);

        $r = app(AccessRequestService::class)->route($r, $this->receptionist->id);
        $this->assertSame('in_approval', $r->status);
        $this->assertSame(1, $r->current_stage_seq);
        $this->assertSame(1, $r->tasks()->where('sequence', 1)->count());
        $this->assertSame('Branch Manager', $r->tasks()->first()->approver_role);
    }

    public function test_approval_chain_advances_and_completes(): void
    {
        $r = app(AccessRequestService::class)->route($this->submitRequest(), $this->receptionist->id);
        $tasks = app(ApprovalTaskService::class);

        // Stage 1: dept manager
        $t1 = $r->tasks()->where('sequence', 1)->first();
        $tasks->decide($t1, $this->deptManager, 'approved', 'OK');
        $this->assertSame(2, $r->fresh()->current_stage_seq);

        // Stage 2: HR
        $t2 = $r->tasks()->where('sequence', 2)->first();
        $tasks->decide($t2, $this->hrAdmin, 'approved');
        $this->assertSame(3, $r->fresh()->current_stage_seq);

        // Stage 3: IT → completed → approved
        $t3 = $r->tasks()->where('sequence', 3)->first();
        $tasks->decide($t3, $this->itAdmin, 'approved');
        $this->assertSame('approved', $r->fresh()->status);
    }

    public function test_unauthorized_user_cannot_decide_task(): void
    {
        $r = app(AccessRequestService::class)->route($this->submitRequest(), $this->receptionist->id);
        $t = $r->tasks()->where('sequence', 1)->first();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(ApprovalTaskService::class)->decide($t, $this->hrAdmin, 'approved');
    }

    public function test_rejection_terminates_request(): void
    {
        $r = app(AccessRequestService::class)->route($this->submitRequest(), $this->receptionist->id);
        $t = $r->tasks()->where('sequence', 1)->first();
        app(ApprovalTaskService::class)->decide($t, $this->deptManager, 'rejected', 'Not needed');
        $r = $r->fresh();
        $this->assertSame('rejected', $r->status);
        $this->assertSame('Not needed', $r->decision_reason);
    }

    public function test_applicant_cannot_approve_own_request(): void
    {
        $user = User::factory()->create(['status' => AccountStatus::Active]);
        $employee = $this->employeeFor($user);
        $user->assignRole('Branch Manager'); // they'd be eligible by role

        $r = $this->submitRequest(['type' => 'existing_employee', 'employee_id' => $employee->id]);
        $r = app(AccessRequestService::class)->route($r, $this->receptionist->id);
        $t = $r->tasks()->where('sequence', 1)->first();
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(ApprovalTaskService::class)->decide($t, $user, 'approved');
    }

    public function test_provisioning_new_employee_creates_pending_user_and_invite(): void
    {
        $r = $this->routeAndApproveAll($this->submitRequest());
        $user = app(AccessProvisioningService::class)->provision($r, ['roles' => ['employee']], $this->itAdmin);
        $this->assertSame(AccountStatus::Pending, $user->status);
        $this->assertTrue($user->hasRole('employee'));
        $this->assertSame('provisioned', $r->fresh()->status);
        $this->assertSame($user->id, $r->fresh()->provisioned_user_id);
        Notification::assertSentTo($user, AccountActivationInvitation::class);
    }

    public function test_external_provisioning_creates_timeboxed_profile(): void
    {
        $sponsor = $this->deptManager;
        $r = $this->submitRequest([
            'type' => 'external', 'organization' => 'Acme Ltd',
            'email' => 'contractor@acme.com', 'sponsor_id' => $sponsor->id,
            'expiry_date' => now()->addMonths(3)->toDateString(),
        ]);
        $r = $this->routeAndApproveAll($r);
        $user = app(AccessProvisioningService::class)->provision(
            $r, ['roles' => ['employee'], 'category' => 'contractor'], $this->itAdmin);

        $profile = ExternalUserProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($profile);
        $this->assertSame('contractor', $profile->category);
        $this->assertSame($sponsor->id, $profile->sponsor_id);
        $this->assertSame($r->expiry_date->toDateString(), $profile->expires_on->toDateString());
    }

    public function test_cannot_provision_unapproved_request(): void
    {
        $r = $this->submitRequest();
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(AccessProvisioningService::class)->provision($r, [], $this->itAdmin);
    }

    public function test_existing_employee_provision_updates_not_duplicates(): void
    {
        $empUser = User::factory()->create(['status' => AccountStatus::Active]);
        $employee = $this->employeeFor($empUser);
        $count = User::count();
        $r = $this->submitRequest(['type' => 'existing_employee', 'employee_id' => $employee->id]);
        $r = $this->routeAndApproveAll($r);
        $user = app(AccessProvisioningService::class)->provision($r, ['roles' => ['employee']], $this->itAdmin);
        $this->assertSame($employee->user_id, $user->id);
        $this->assertSame($count, User::count()); // no new user
    }

    public function test_tracking_requires_reference_and_token(): void
    {
        $r = $this->submitRequest();
        $this->assertNotNull(app(AccessRequestService::class)->findForTracking($r->reference, $r->tracking_token));
        $this->assertNull(app(AccessRequestService::class)->findForTracking($r->reference, 'wrong'));
    }

    public function test_public_portal_routes(): void
    {
        $this->get(route('access.landing'))->assertOk();
        $this->get(route('access.form', 'new_employee'))->assertOk();
        $this->get(route('access.track'))->assertOk();
        $this->get(route('access.form', 'bogus'))->assertNotFound();
    }

    public function test_internal_routes_require_permission(): void
    {
        $r = $this->submitRequest();
        $plain = $this->userWithRole('employee');
        $this->actingAs($plain)->get(route('reception.access.requests.index'))->assertForbidden();
        $this->actingAs($this->receptionist)->get(route('reception.access.requests.index'))->assertOk();
    }

    public function test_receptionist_cannot_provision(): void
    {
        $r = $this->routeAndApproveAll($this->submitRequest());
        $this->actingAs($this->receptionist)
            ->get(route('reception.access.provisioning.configure', $r))->assertForbidden();
    }

    public function test_api_submit_and_track(): void
    {
        $res = $this->postJson('/api/v1/access-requests', [
            'type' => 'new_employee', 'full_name' => 'Api User',
            'email' => 'api@example.com', 'privacy_ack' => true,
        ]);
        $res->assertStatus(201)->assertJsonStructure(['reference', 'tracking_token']);

        $body = $res->json();
        $this->getJson('/api/v1/access-requests/track?reference='.$body['reference'].'&token='.$body['tracking_token'])
            ->assertOk()->assertJson(['status' => 'submitted']);
        $this->getJson('/api/v1/access-requests/track?reference='.$body['reference'].'&token=bad')
            ->assertNotFound();
    }

    private function routeAndApproveAll(AccessRequest $r): AccessRequest
    {
        $r = app(AccessRequestService::class)->route($r, $this->receptionist->id);
        $taskSvc = app(ApprovalTaskService::class);
        $byRole = [
            'Branch Manager' => $this->deptManager,
            'CTO and Admin' => $this->hrAdmin,
            'CTO and Admin' => $this->itAdmin,
        ];
        for ($i = 0; $i < 6; $i++) {
            $t = $r->fresh()->tasks()->where('status', 'pending')->first();
            if (! $t) break;
            $taskSvc->decide($t, $byRole[$t->approver_role] ?? $this->itAdmin, 'approved');
        }
        return $r->fresh();
    }

    public function test_documents_attach_to_request_and_download(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $r = $this->submitRequest();

        $file = \Illuminate\Http\UploadedFile::fake()->create('passport.pdf', 100, 'application/pdf');
        $n = app(\Modules\Reception\Services\AccessRequestService::class)
            ->attachDocuments($r, [$file], $this->itAdmin->id, 'id_document');

        $this->assertSame(1, $n);
        $this->assertDatabaseHas('access_request_documents',
            ['request_id' => $r->id, 'category' => 'id_document']);
        $this->assertDatabaseHas('access_request_history',
            ['request_id' => $r->id, 'event' => 'documents.attached']);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists(
            $r->fresh()->documents->first()->file->path);

        // Authorized download.
        $doc = $r->fresh()->documents->first();
        $this->actingAs($this->receptionist ?? $this->itAdmin)
            ->get(route('reception.access.requests.documents.download', [$r->id, $doc->id]))
            ->assertOk();
    }

    public function test_public_submission_accepts_document_uploads(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->post('/access-request', [
            'type' => 'new_employee', 'full_name' => 'Docs Test',
            'email' => 'docs@example.com', 'privacy_ack' => '1',
            'branch_id' => $this->branch()->id, 'department_id' => $this->department()->id,
            'documents' => [\Illuminate\Http\UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf')],
        ])->assertRedirect();
        $this->assertDatabaseCount('access_request_documents', 1);
    }

    public function test_expired_external_profiles_are_suspended(): void
    {
        $expired = User::factory()->create(['status' => AccountStatus::Active]);
        $valid = User::factory()->create(['status' => AccountStatus::Active]);

        \Modules\Reception\Models\ExternalUserProfile::create([
            'user_id' => $expired->id, 'category' => 'contractor',
            'organization' => 'Acme', 'sponsor_id' => $this->itAdmin->id,
            'expires_on' => now()->subDay()->toDateString(),
        ]);
        \Modules\Reception\Models\ExternalUserProfile::create([
            'user_id' => $valid->id, 'category' => 'vendor',
            'organization' => 'Acme', 'sponsor_id' => $this->itAdmin->id,
            'expires_on' => now()->addMonth()->toDateString(),
        ]);

        $n = app(\Modules\Reception\Services\AccessExpirationService::class)->suspendExpired();

        $this->assertSame(1, $n);
        $this->assertTrue($expired->fresh()->isSuspended());
        $this->assertFalse($valid->fresh()->isSuspended());

        // Idempotent — second run suspends nobody.
        $this->assertSame(0, app(\Modules\Reception\Services\AccessExpirationService::class)->suspendExpired());
    }
}

