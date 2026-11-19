<?php

namespace Tests\Feature\Platform;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_and_permissions_seed_correctly(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'super-admin']);
        $this->assertDatabaseHas('permissions', ['name' => 'system.view']);
    }

    public function test_system_page_requires_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $plainUser = User::factory()->create();

        $this->actingAs($plainUser)
            ->get('/admin/system')
            ->assertStatus(403);
    }

    public function test_super_admin_can_access_system_page(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->get('/admin/system')
            ->assertOk()
            ->assertSee('System health');
    }

    public function test_suspended_user_is_blocked_from_app(): void
    {
        $user = User::factory()->create(['status' => 'suspended']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_pending_user_is_blocked_from_app(): void
    {
        $user = User::factory()->create(['status' => 'pending']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');
    }
}
