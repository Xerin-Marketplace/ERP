<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Models\RegistrationApplication;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder::class);
        $this->seed(\Modules\Identity\Database\Seeders\IdentityDatabaseSeeder::class);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '+255712345678',
        ], $overrides);
    }

    public function test_registration_screen_renders(): void
    {
        $this->get('/register')->assertOk()->assertSee('Apply for ERP access');
    }

    public function test_public_registration_creates_application_not_user(): void
    {
        $this->post('/register', $this->payload())->assertRedirect(route('register.track'));

        $this->assertDatabaseHas('registration_applications', [
            'email' => 'test@example.com',
            'status' => 'submitted',
        ]);
        // CRITICAL: no internal access is created by registering
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_application_gets_reference_number(): void
    {
        $this->post('/register', $this->payload());

        $app = RegistrationApplication::firstOrFail();
        $this->assertMatchesRegularExpression('/^XERIN-REG-\d{4}-\d{6}$/', $app->reference);
    }

    public function test_duplicate_open_application_is_rejected(): void
    {
        $this->post('/register', $this->payload());
        $this->post('/register', $this->payload())->assertSessionHasErrors('email');
    }

    public function test_email_of_existing_user_is_rejected(): void
    {
        \App\Models\User::factory()->create(['email' => 'taken@example.com', 'status' => 'active']);

        $this->post('/register', $this->payload(['email' => 'taken@example.com']))
            ->assertSessionHasErrors('email');
    }

    public function test_applicant_can_track_status_with_reference_and_email(): void
    {
        $this->post('/register', $this->payload());
        $app = RegistrationApplication::firstOrFail();

        $this->post('/register/track', [
            'reference' => $app->reference,
            'email' => 'test@example.com',
        ])->assertOk()->assertSee($app->reference);
    }

    public function test_tracking_rejects_wrong_email(): void
    {
        $this->post('/register', $this->payload());
        $app = RegistrationApplication::firstOrFail();

        $this->post('/register/track', [
            'reference' => $app->reference,
            'email' => 'wrong@example.com',
        ])->assertSessionHasErrors('reference');
    }

    public function test_applicant_cannot_access_internal_erp(): void
    {
        $this->post('/register', $this->payload());

        // No session exists — protected routes still demand authentication
        $this->get('/dashboard')->assertRedirect('/login');
        $this->getJson('/api/v1/organization/overview')->assertStatus(401);
    }
}
