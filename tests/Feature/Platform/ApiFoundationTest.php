<?php

namespace Tests\Feature\Platform;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_standard_envelope(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk()
            ->assertJsonStructure(['success', 'message', 'data' => ['status', 'checks']])
            ->assertJson(['success' => true]);
    }

    public function test_api_token_can_be_issued_and_revoked(): void
    {
        $user = User::factory()->create([
            'email' => 'api@xerin.test',
            'password' => bcrypt('password123'),
        ]);

        $tokenResponse = $this->postJson('/api/v1/auth/token', [
            'email' => 'api@xerin.test',
            'password' => 'password123',
            'device_name' => 'pest-test',
        ]);

        $tokenResponse->assertOk()->assertJson(['success' => true]);
        $token = $tokenResponse->json('data.token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJson(['data' => ['email' => 'api@xerin.test']]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/revoke')
            ->assertOk();
    }

    public function test_api_rejects_wrong_credentials(): void
    {
        User::factory()->create(['email' => 'a@b.c', 'password' => bcrypt('right')]);

        $this->postJson('/api/v1/auth/token', ['email' => 'a@b.c', 'password' => 'wrong'])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_suspended_account_cannot_get_token(): void
    {
        User::factory()->create([
            'email' => 'sus@xerin.test',
            'password' => bcrypt('password123'),
            'status' => 'suspended',
        ]);

        $this->postJson('/api/v1/auth/token', ['email' => 'sus@xerin.test', 'password' => 'password123'])
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_protected_route_requires_auth(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401)->assertJson(['success' => false]);
    }

    public function test_missing_api_route_returns_404_envelope(): void
    {
        $this->getJson('/api/v1/does-not-exist')
            ->assertStatus(404)
            ->assertJson(['success' => false, 'message' => 'Resource not found.']);
    }
}
