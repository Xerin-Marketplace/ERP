<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use Modules\Identity\Services\RoleAssignmentGuard;
use Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessControlPhase2Test extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $groupAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(OrganizationDatabaseSeeder::class);
        $this->seed(IdentityDatabaseSeeder::class);

        $this->superAdmin = User::factory()->create(['status' => 'active']);
        $this->superAdmin->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web'])
            ->givePermissionTo(Permission::pluck('name')));

        $this->groupAdmin = User::factory()->create(['status' => 'active']);
        $this->groupAdmin->assignRole('group-administrator');
    }

    private function plainUser(): User
    {
        return User::factory()->create(['status' => 'active']);
    }

    /* ------------------------- Roles & permissions seeded ------------------------- */

    public function test_spec_roles_exist(): void
    {
        foreach (['super-admin', 'group-administrator', 'branch-manager',
                  'hr-administrator', 'it-administrator', 'it-officer',
                  'receptionist', 'department-manager', 'employee'] as $role) {
            $this->assertTrue(Role::where('name', $role)->exists(), "missing role {$role}");
        }
    }

    public function test_canonical_permissions_seeded(): void
    {
        foreach (['users.view', 'users.create', 'users.assign_roles', 'users.assign_permissions',
                  'roles.view', 'roles.assign', 'permissions.view', 'permissions.manage',
                  'access.audit.view', 'organization.branches.manage'] as $perm) {
            $this->assertTrue(Permission::where('name', $perm)->exists(), "missing {$perm}");
        }
    }

    public function test_identity_seeder_does_not_strip_organization_grants(): void
    {
        $this->seed(OrganizationDatabaseSeeder::class); // re-run
        $this->seed(IdentityDatabaseSeeder::class);     // re-run — must not wipe org perms

        $role = Role::where('name', 'group-administrator')->first();
        $this->assertTrue($role->hasPermissionTo('organization.branches.manage'));
        $this->assertTrue($role->hasPermissionTo('users.assign_roles'));
    }

    /* ------------------------- User management ------------------------- */

    public function test_admin_can_invite_user(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/identity/users', [
                'name' => 'New Staff', 'email' => 'staff@test.dev', 'role' => 'employee',
            ])->assertRedirect();

        $user = User::where('email', 'staff@test.dev')->first();
        $this->assertNotNull($user);
        $this->assertEquals('pending', $user->status->value ?? $user->status);
        $this->assertTrue($user->hasRole('employee'));
        $this->assertDatabaseHas('access_invitations', ['user_id' => $user->id]);
    }

    public function test_user_suspend_activate(): void
    {
        $target = $this->plainUser();
        $this->actingAs($this->superAdmin)
            ->post("/identity/users/{$target->id}/suspend")->assertRedirect();
        $this->assertEquals('suspended', $target->fresh()->status->value ?? $target->fresh()->status);

        $this->actingAs($this->superAdmin)
            ->post("/identity/users/{$target->id}/activate")->assertRedirect();
        $this->assertEquals('active', $target->fresh()->status->value ?? $target->fresh()->status);
    }

    /* ------------------------- Role management ------------------------- */

    public function test_role_create_clone_and_protected_delete(): void
    {
        // create
        $this->actingAs($this->superAdmin)->post('/identity/roles', [
            'name' => 'test-role', 'permissions' => ['users.view'],
        ]);
        $this->assertDatabaseHas('roles', ['name' => 'test-role']);

        // clone
        $role = Role::where('name', 'test-role')->first();
        $this->actingAs($this->superAdmin)->post("/identity/roles/{$role->id}/clone", [
            'name' => 'test-role-copy',
        ]);
        $this->assertDatabaseHas('roles', ['name' => 'test-role-copy']);

        // protected role cannot be deleted
        $su = Role::where('name', 'super-admin')->first();
        $this->actingAs($this->superAdmin)
            ->delete("/identity/roles/{$su->id}")->assertForbidden();
    }

    /* ------------------------- Privilege escalation ------------------------- */

    public function test_group_admin_cannot_assign_super_admin(): void
    {
        $target = $this->plainUser();
        $this->actingAs($this->groupAdmin)
            ->post("/identity/users/{$target->id}/role", ['role' => 'super-admin']);
        $this->assertFalse($target->fresh()->hasRole('super-admin'));
    }

    public function test_plain_user_cannot_self_elevate(): void
    {
        $guard = app(RoleAssignmentGuard::class);
        $plain = $this->plainUser();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $guard->assertCanAssign($plain, 'group-administrator', $plain);
    }

    public function test_group_admin_can_assign_same_or_lower_tier(): void
    {
        $guard = app(RoleAssignmentGuard::class);
        $target = $this->plainUser();

        // group-administrator (80) may assign group-administrator (80) to others
        $guard->assertCanAssign($this->groupAdmin, 'group-administrator', $target);
        // but not super-admin (100)
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $guard->assertCanAssign($this->groupAdmin, 'super-admin', $target);
    }

    /* ------------------------- Permission matrix ------------------------- */

    public function test_permission_matrix_sync_with_audit(): void
    {
        $role = Role::firstOrCreate(['name' => 'matrix-test', 'guard_name' => 'web']);

        $this->actingAs($this->superAdmin)
            ->put("/identity/permissions/matrix/{$role->id}", [
                'permissions' => ['users.view', 'roles.view'],
            ])->assertRedirect();

        $this->assertTrue($role->fresh()->hasPermissionTo('users.view'));
        $this->assertFalse($role->fresh()->hasPermissionTo('users.create'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'role.permissions_synced']);
    }

    public function test_matrix_rejects_overreach_for_non_super_admin(): void
    {
        $role = Role::firstOrCreate(['name' => 'limited', 'guard_name' => 'web']);
        // group-admin has users.assign_roles but NOT permissions.manage → route 403
        $this->actingAs($this->groupAdmin)
            ->put("/identity/permissions/matrix/{$role->id}", ['permissions' => ['users.view']])
            ->assertForbidden();
    }

    /* ------------------------- Branch scoping (regression) ------------------------- */

    public function test_branch_scope_still_enforced(): void
    {
        $tz = \Modules\Organization\Models\Branch::where('code', 'TZ')->first();
        $dxb = \Modules\Organization\Models\Branch::where('code', 'DXB')->first();
        $scoped = User::factory()->create(['status' => 'active', 'branch_id' => $tz->id]);
        $scoped->givePermissionTo('organization.branches.view');

        $policy = app(\Modules\Organization\Policies\BranchPolicy::class);
        $this->assertTrue($policy->view($scoped, $tz));
        $this->assertFalse($policy->view($scoped, $dxb));
    }

    /* ------------------------- Access administration ------------------------- */

    public function test_access_pages_require_permission(): void
    {
        $plain = $this->plainUser();
        $this->actingAs($plain)->get('/identity/access')->assertForbidden();
        $this->actingAs($plain)->get('/identity/permissions/matrix')->assertForbidden();
        $this->actingAs($this->groupAdmin)->get('/identity/access')->assertOk();
        $this->actingAs($this->groupAdmin)->get('/identity/permissions/matrix')->assertOk();
    }
}
