<?php

namespace Tests\Feature\Platform;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nwidart\Modules\Facades\Module;
use Tests\TestCase;

class ModuleEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_discovery_finds_enabled_modules(): void
    {
        $modules = Module::all();

        $this->assertNotEmpty($modules);
        $this->assertTrue(Module::has('Core'));
        $this->assertTrue(Module::find('Core')->isEnabled());
    }

    public function test_module_routes_are_registered(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('api.core.status'));
    }

    public function test_module_status_endpoint_requires_auth(): void
    {
        $this->getJson('/api/v1/core/status')->assertStatus(401);
    }

    public function test_module_status_endpoint_lists_modules(): void
    {
        $user = \App\Models\User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // Module names are sorted alphabetically — assert membership, not position.
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/core/status')
            ->assertOk()
            ->assertJson(['success' => true]);
        $this->assertContains('Core', collect($response->json('data.modules'))->pluck('name')->all());
    }
}
