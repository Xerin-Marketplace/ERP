<?php

namespace Tests\Feature\Auth;

use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function branch(): Branch
    {
        return Branch::create(['name' => 'Head Office', 'code' => 'HQ', 'city' => 'Dar es Salaam']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'branch_id' => $this->branch()->id,
            'phone_code' => '+255',
            'phone' => '712345678',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->branch();
        $this->get('/register')->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', $this->payload());

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = \App\Models\User::where('email', 'test@example.com')->first();
        $this->assertEquals('+255712345678', $user->phone);
        $this->assertNotNull($user->branch_id);
    }

    public function test_registration_requires_branch_and_phone(): void
    {
        $this->post('/register', [
            'name' => 'T', 'email' => 't@e.com',
            'password' => 'password', 'password_confirmation' => 'password',
        ])->assertSessionHasErrors(['branch_id', 'phone', 'phone_code']);
    }

    public function test_unverified_user_sees_verification_notice(): void
    {
        $this->post('/register', $this->payload());

        $this->get('/dashboard')->assertRedirect(route('verification.notice'));
        $this->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Verify your email')
            ->assertSee('test@example.com');
    }
}
