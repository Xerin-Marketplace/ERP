<?php

namespace Modules\Identity\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use Modules\Identity\Enums\ApplicationStatus;
use Modules\Identity\Models\AccessInvitation;
use Modules\Identity\Models\EmployeeAssignment;
use Modules\Identity\Models\RegistrationApplication;
use Modules\Identity\Services\AssignmentService;
use Modules\Identity\Services\InvitationService;
use Modules\Identity\Services\ProvisioningService;
use Modules\Identity\Services\RegistrationService;
use Modules\Identity\Services\UserLifecycleService;
use Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;
use Tests\TestCase;

class IdentityModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(OrganizationDatabaseSeeder::class);
        $this->seed(IdentityDatabaseSeeder::class);
    }

    private function admin(): User
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole('super-admin');
        return $u;
    }

    private function itOfficer(): User
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole('it-officer');
        return $u;
    }

    private function employee(): User
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole('employee');
        return $u;
    }

    private function company(): Company
    {
        return Company::where('code', 'XG')->firstOrFail();
    }

    private function submitApplication(array $overrides = []): RegistrationApplication
    {
        return app(RegistrationService::class)->submit(array_merge([
            'first_name' => 'Jane',
            'last_name' => 'Applicant',
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+255700000000',
        ], $overrides));
    }

    private function approvedApplication(): RegistrationApplication
    {
        $admin = $this->admin();
        $app = $this->submitApplication();
        $svc = app(RegistrationService::class);
        $svc->transition($app, ApplicationStatus::UnderReview, $admin->id);
        $svc->approve($app, $admin->id);
        return $app->fresh();
    }

    /* --------------------------- Workflow ---------------------------- */

    public function test_workflow_rejects_invalid_transitions(): void
    {
        $app = $this->submitApplication();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(RegistrationService::class)->approve($app, $this->admin()->id); // submitted → approved not allowed
    }

    public function test_rejection_requires_reason_via_controller(): void
    {
        $admin = $this->admin();
        $app = $this->submitApplication();
        app(RegistrationService::class)->transition($app, ApplicationStatus::UnderReview, $admin->id);

        $this->actingAs($admin)
            ->post("/identity/registrations/{$app->id}/reject", ['reason' => ''])
            ->assertSessionHasErrors('reason');
    }

    public function test_information_request_stores_message_and_status(): void
    {
        $admin = $this->admin();
        $app = $this->submitApplication();
        app(RegistrationService::class)->transition($app, ApplicationStatus::UnderReview, $admin->id);

        $this->actingAs($admin)
            ->post("/identity/registrations/{$app->id}/request-info", ['message' => 'Please upload your ID'])
            ->assertRedirect();

        $app->refresh();
        $this->assertSame(ApplicationStatus::InformationRequested, $app->status);
        $this->assertSame('Please upload your ID', $app->applicant_message);
    }

    public function test_every_transition_is_recorded_in_history(): void
    {
        $admin = $this->admin();
        $app = $this->submitApplication();
        $svc = app(RegistrationService::class);
        $svc->transition($app, ApplicationStatus::UnderReview, $admin->id);
        $svc->approve($app, $admin->id);

        $this->assertSame(3, $app->statusHistories()->count()); // submit + review + approve
        $this->assertDatabaseHas('registration_status_histories', [
            'registration_application_id' => $app->id,
            'to_status' => 'approved',
        ]);
    }

    /* -------------------------- Permissions --------------------------- */

    public function test_unauthorized_user_cannot_view_registrations(): void
    {
        $this->actingAs($this->employee())->get('/identity/registrations')->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_approve(): void
    {
        $employee = $this->employee();
        $app = $this->submitApplication();
        app(RegistrationService::class)->transition($app, ApplicationStatus::UnderReview, $this->admin()->id);

        $this->actingAs($employee)
            ->post("/identity/registrations/{$app->id}/approve")
            ->assertStatus(403);
    }

    public function test_it_officer_can_review_applications(): void
    {
        $officer = $this->itOfficer();
        $app = $this->submitApplication();

        $this->actingAs($officer)
            ->post("/identity/registrations/{$app->id}/review")
            ->assertRedirect();

        $this->assertNotSame(ApplicationStatus::Submitted, $app->fresh()->status);
    }

    public function test_registration_dashboard_shows_stats(): void
    {
        $this->submitApplication();
        $this->submitApplication();

        $this->actingAs($this->itOfficer())
            ->get('/identity/registrations')
            ->assertOk()
            ->assertSee('XERIN-REG-');
    }

    /* -------------------------- Provisioning -------------------------- */

    public function test_provisioning_creates_full_account(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $app = $this->approvedApplication();
        $dept = Department::create(['code' => 'IT', 'name' => 'IT Dept', 'company_id' => $this->company()->id]);

        ['user' => $user, 'token' => $token] = app(ProvisioningService::class)->provision($app, [
            'company_id' => $this->company()->id,
            'branch_id' => Branch::where('code', 'HQ')->value('id') ?? Branch::create(['name' => 'HQ', 'code' => 'HQ', 'branch_type' => 'head_office', 'company_id' => $this->company()->id])->id,
            'department_id' => $dept->id,
            'position' => 'Developer',
            'employee_number' => 'XER-00042',
        ], 'employee', $admin->id);

        $this->assertSame('pending', $user->status->value);
        $this->assertTrue($user->hasRole('employee'));
        $this->assertSame('XER-00042', $user->profile->employee_number);
        $this->assertDatabaseHas('employee_assignments', [
            'user_id' => $user->id, 'company_id' => $this->company()->id, 'status' => 'active',
        ]);
        $this->assertNotNull($token);
        $this->assertDatabaseHas('access_invitations', ['user_id' => $user->id]);
        $this->assertSame(ApplicationStatus::Provisioning, $app->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'provisioning.completed']);
    }

    public function test_provisioned_user_cannot_login_until_activation(): void
    {
        ['user' => $user] = app(ProvisioningService::class)->provision(
            $this->approvedApplication(),
            ['company_id' => $this->company()->id],
            'employee',
            $this->admin()->id,
        );

        // Pending status → blocked from the app (verified/account.active middleware)
        $this->actingAs($user)->get('/dashboard')->assertRedirect();
    }

    public function test_invalid_branch_company_relationship_rejected(): void
    {
        $other = Company::create(['code' => 'OTH', 'legal_name' => 'Other', 'company_type' => 'entity']);
        $foreignBranch = Branch::create(['name' => 'Foreign', 'code' => 'FRN', 'branch_type' => 'branch', 'company_id' => $other->id]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(AssignmentService::class)->validatePlacement($this->company()->id, $foreignBranch->id, null);
    }

    public function test_cross_company_department_rejected(): void
    {
        $other = Company::create(['code' => 'OTH2', 'legal_name' => 'Other2', 'company_type' => 'entity']);
        $dept = Department::create(['code' => 'X', 'name' => 'X', 'company_id' => $other->id]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(AssignmentService::class)->validatePlacement($this->company()->id, null, $dept->id);
    }

    public function test_super_admin_cannot_be_provisioned(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(ProvisioningService::class)->provision(
            $this->approvedApplication(),
            ['company_id' => $this->company()->id],
            'super-admin',
            $this->admin()->id,
        );
    }

    public function test_user_without_role_assign_cannot_change_roles(): void
    {
        $employee = $this->employee();
        $target = User::factory()->create(['status' => 'active']);

        $this->actingAs($employee)
            ->post("/identity/users/{$target->id}/role", ['role' => 'employee'])
            ->assertStatus(403);
    }

    public function test_user_cannot_change_own_role(): void
    {
        $admin = $this->admin();
        $admin->removeRole('super-admin');
        $admin->givePermissionTo('role.assign');

        $this->actingAs($admin)
            ->post("/identity/users/{$admin->id}/role", ['role' => 'employee'])
            ->assertStatus(422);
    }

    /* --------------------------- Activation --------------------------- */

    public function test_activation_sets_password_and_activates(): void
    {
        ['token' => $token] = app(ProvisioningService::class)->provision(
            $this->approvedApplication(),
            ['company_id' => $this->company()->id],
            'employee',
            $this->admin()->id,
        );

        $this->post("/activate/{$token}", [
            'password' => 'Str0ng!Password', 'password_confirmation' => 'Str0ng!Password',
        ])->assertRedirect(route('dashboard'));

        $user = User::where('email', RegistrationApplication::first()->email)->firstOrFail();
        $this->assertSame('active', $user->status->value);
        $this->assertSame(ApplicationStatus::Active, RegistrationApplication::first()->status);
    }

    public function test_invalid_invitation_token_rejected(): void
    {
        $this->get('/activate/not-a-real-token')->assertOk()->assertSee('unavailable');
    }

    public function test_expired_invitation_rejected(): void
    {
        ['token' => $token, 'invitation' => $inv] = app(InvitationService::class)->generate(
            User::factory()->create(['status' => 'pending'])
        );
        $inv->update(['expires_at' => now()->subHour()]);

        $this->get("/activate/{$token}")->assertOk()->assertSee('expired');
    }

    public function test_invitation_is_single_use(): void
    {
        ['token' => $token] = app(ProvisioningService::class)->provision(
            $this->approvedApplication(),
            ['company_id' => $this->company()->id],
            'employee',
            $this->admin()->id,
        );

        $payload = ['password' => 'Str0ng!Password', 'password_confirmation' => 'Str0ng!Password'];
        $this->post("/activate/{$token}", $payload);

        // Second use → invalid
        auth()->logout();
        $this->get("/activate/{$token}")->assertOk()->assertSee('already been used');
    }

    public function test_new_invitation_revokes_previous(): void
    {
        $user = User::factory()->create(['status' => 'pending']);
        $svc = app(InvitationService::class);

        ['invitation' => $first] = $svc->generate($user);
        $svc->generate($user);

        $this->assertNotNull($first->fresh()->revoked_at);
    }

    /* --------------------------- Lifecycle ---------------------------- */

    public function test_suspension_revokes_sessions_and_tokens(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['status' => 'active']);
        $token = $user->createToken('test')->plainTextToken;

        $this->lifecycle()->suspend($user, $admin->id, 'policy violation');

        $this->assertSame(0, $user->tokens()->count());
        $this->assertSame('suspended', $user->fresh()->status->value);
        $this->assertDatabaseHas('account_security_events', ['user_id' => $user->id, 'type' => 'access_revoked']);
    }

    public function test_suspended_user_cannot_access_api(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $token = $user->createToken('t')->plainTextToken;
        app(UserLifecycleService::class)->suspend($user, $this->admin()->id);

        // Even a fresh token on suspended account is blocked by account.active
        $newToken = $user->fresh()->createToken('t2')->plainTextToken;
        $this->getJson('/api/v1/identity/me', ['Authorization' => "Bearer {$newToken}"])
            ->assertStatus(403);
    }

    public function test_admin_can_suspend_via_web_and_audit_records_it(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($admin)
            ->post("/identity/users/{$user->id}/suspend", ['reason' => 'test'])
            ->assertRedirect();

        $this->assertSame('suspended', $user->fresh()->status->value);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.suspended', 'resource_id' => $user->id]);
    }

    /* --------------------------- Assignments -------------------------- */

    public function test_assignment_history_is_never_overwritten(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['status' => 'active']);
        $svc = app(AssignmentService::class);
        $company = $this->company();

        $a1 = $svc->assign($user, ['company_id' => $company->id], $admin->id, 'new');
        $b2 = Branch::create(['name' => 'New Br', 'code' => 'NB', 'branch_type' => 'branch', 'company_id' => $company->id]);
        $a2 = $svc->assign($user, ['company_id' => $company->id, 'branch_id' => $b2->id], $admin->id, 'transfer');

        $a1->refresh();
        $this->assertSame('ended', $a1->status);           // preserved, ended
        $this->assertNotNull($a1->effective_end);
        $this->assertSame('active', $a2->status);
        $this->assertSame(2, EmployeeAssignment::where('user_id', $user->id)->count());
        $this->assertSame($b2->id, $user->fresh()->branch_id);
    }

    /* ------------------------------- API ------------------------------ */

    public function test_api_registration_returns_reference(): void
    {
        $this->postJson('/api/v1/identity/register', [
            'first_name' => 'Api', 'last_name' => 'User',
            'email' => 'api@example.com', 'phone' => '+255700000001',
        ])->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['reference', 'status']]);
    }

    public function test_api_status_lookup_requires_matching_email(): void
    {
        $app = $this->submitApplication();

        $this->getJson("/api/v1/identity/registration/status/{$app->reference}?email=wrong@x.com")
            ->assertStatus(404);

        $this->getJson("/api/v1/identity/registration/status/{$app->reference}?email={$app->email}")
            ->assertOk()->assertJson(['success' => true]);
    }

    public function test_api_admin_requires_permission(): void
    {
        Sanctum::actingAs($this->employee());
        $this->getJson('/api/v1/identity/admin/registrations')->assertStatus(403);

        Sanctum::actingAs($this->itOfficer());
        $this->getJson('/api/v1/identity/admin/registrations')->assertOk();
    }

    public function test_internal_review_notes_never_exposed_publicly(): void
    {
        $admin = $this->admin();
        $app = $this->submitApplication();
        app(RegistrationService::class)->addInternalNote($app, $admin->id, 'SECRET NOTE');

        $timeline = app(RegistrationService::class)->publicTimeline($app->fresh());
        $this->assertStringNotContainsString('SECRET NOTE', json_encode($timeline));
    }

    /* --------------------------- Misc pages --------------------------- */

    public function test_users_index_and_show_render(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/identity/users')->assertOk();
        $this->actingAs($admin)->get("/identity/users/{$admin->id}")->assertOk();
    }

    public function test_roles_pages_render_and_matrix_works(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/identity/roles')->assertOk()->assertSee('it-officer');

        $this->actingAs($admin)->post('/identity/roles', [
            'name' => 'test-role', 'permissions' => ['user.view'],
        ])->assertRedirect();

        $this->assertDatabaseHas('roles', ['name' => 'test-role']);
    }

    public function test_protected_role_cannot_be_edited_or_deleted(): void
    {
        $admin = $this->admin();
        $super = \Spatie\Permission\Models\Role::where('name', 'super-admin')->firstOrFail();

        $this->actingAs($admin)->get("/identity/roles/{$super->id}/edit")->assertStatus(403);
        $this->actingAs($admin)->delete("/identity/roles/{$super->id}")->assertStatus(403);
    }

    public function test_me_page_shows_own_placement(): void
    {
        $user = $this->employee();
        app(AssignmentService::class)->assign(
            $user, ['company_id' => $this->company()->id], $this->admin()->id
        );

        $this->actingAs($user)->get('/identity/me')->assertOk()->assertSee('Xerin Group');
    }

    public function test_audit_records_generated_throughout(): void
    {
        $admin = $this->admin();
        $app = $this->submitApplication();
        app(RegistrationService::class)->transition($app, ApplicationStatus::UnderReview, $admin->id);
        app(RegistrationService::class)->approve($app, $admin->id);

        foreach (['registration.submitted', 'registration.under_review', 'registration.approved'] as $action) {
            $this->assertDatabaseHas('audit_logs', ['action' => $action]);
        }
    }

    private function lifecycle(): UserLifecycleService
    {
        return app(UserLifecycleService::class);
    }
}
