<?php

namespace Modules\Identity\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Database\Seeders\IdentityDatabaseSeeder;
use Modules\Identity\Services\AdminDelegationService;
use Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\BranchAdministrator;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDelegationTest extends TestCase
{
    use RefreshDatabase;

    private AdminDelegationService $svc;
    private User $super;
    private Branch $dubai;
    private Branch $tz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(OrganizationDatabaseSeeder::class);
        $this->seed(IdentityDatabaseSeeder::class);
        $this->svc = app(AdminDelegationService::class);
        $this->super = User::factory()->create(['status' => 'active']);
        $this->super->assignRole('super-admin');
        $branches = Branch::orderBy('id')->take(2)->get();
        $this->dubai = $branches[0];
        $this->tz = $branches[1];
    }

    private function branchAdmin(Branch $b, array $roles = ['employee'], array $perms = []): User
    {
        $u = User::factory()->create(['status' => 'active', 'branch_id' => $b->id]);
        $this->svc->assignBranchAdmin($this->super, $u, $b->id, $roles, $perms);
        return $u->fresh();
    }

    public function test_super_admin_assigns_branch_admin_with_delegation(): void
    {
        $admin = $this->branchAdmin($this->dubai, ['employee', 'Branch Manager'], ['users.view']);
        $this->assertTrue($admin->hasRole('branch-admin'));
        $this->assertSame($this->dubai->id, $this->svc->adminBranchId($admin));
        $del = BranchAdministrator::where('user_id', $admin->id)->first();
        $this->assertEquals(['employee', 'Branch Manager'], $del->delegable_roles);
    }

    public function test_non_super_admin_cannot_delegate(): void
    {
        $actor = User::factory()->create(['status' => 'active']);
        $target = User::factory()->create(['status' => 'active']);
        $this->expectException(ValidationException::class);
        $this->svc->assignBranchAdmin($actor, $target, $this->dubai->id, [], []);
    }

    public function test_branch_admin_assigns_delegable_role_to_same_branch_user(): void
    {
        $admin = $this->branchAdmin($this->dubai, ['employee']);
        $target = User::factory()->create(['status' => 'active', 'branch_id' => $this->dubai->id]);
        $this->svc->assertCanAssignRole($admin, 'employee', $target);
        $this->addToAssertionCount(1);
    }

    public function test_branch_admin_cannot_assign_super_admin(): void
    {
        $admin = $this->branchAdmin($this->dubai, ['super-admin', 'employee']);
        $target = User::factory()->create(['status' => 'active', 'branch_id' => $this->dubai->id]);
        $this->expectException(ValidationException::class);
        $this->svc->assertCanAssignRole($admin, 'super-admin', $target);
    }

    public function test_branch_admin_cannot_assign_role_outside_delegation(): void
    {
        $admin = $this->branchAdmin($this->dubai, ['employee']);
        $target = User::factory()->create(['status' => 'active', 'branch_id' => $this->dubai->id]);
        $this->expectException(ValidationException::class);
        $this->svc->assertCanAssignRole($admin, 'Branch Manager', $target);
    }

    public function test_branch_admin_cannot_act_on_other_branch_user(): void
    {
        $admin = $this->branchAdmin($this->dubai, ['employee']);
        $tzUser = User::factory()->create(['status' => 'active', 'branch_id' => $this->tz->id]);
        $this->expectException(ValidationException::class);
        $this->svc->assertCanActOn($admin, $tzUser);
    }

    public function test_branch_admin_cannot_act_on_super_admin(): void
    {
        $admin = $this->branchAdmin($this->dubai, ['employee']);
        $this->expectException(ValidationException::class);
        $this->svc->assertCanActOn($admin, $this->super);
    }

    public function test_branch_admin_cannot_grant_permission_outside_delegation(): void
    {
        $admin = $this->branchAdmin($this->dubai, ['employee'], ['users.view']);
        $target = User::factory()->create(['status' => 'active', 'branch_id' => $this->dubai->id]);
        $this->expectException(ValidationException::class);
        $this->svc->assertCanGrantPermission($admin, 'users.delete', $target);
    }

    public function test_suspended_delegation_has_zero_authority(): void
    {
        $admin = $this->branchAdmin($this->dubai, ['employee']);
        $del = BranchAdministrator::where('user_id', $admin->id)->first();
        $this->svc->suspend($this->super, $del);
        $target = User::factory()->create(['status' => 'active', 'branch_id' => $this->dubai->id]);
        $this->expectException(ValidationException::class);
        $this->svc->assertCanAssignRole($admin->fresh(), 'employee', $target);
    }

    public function test_expired_delegation_has_zero_authority(): void
    {
        $u = User::factory()->create(['status' => 'active', 'branch_id' => $this->dubai->id]);
        $this->svc->assignBranchAdmin($this->super, $u, $this->dubai->id, ['employee'], [], now()->subDay());
        $this->assertFalse($this->svc->isBranchAdmin($u->fresh()));
    }

    public function test_revoked_delegation_removes_role(): void
    {
        $admin = $this->branchAdmin($this->dubai);
        $del = BranchAdministrator::where('user_id', $admin->id)->first();
        $this->svc->revoke($this->super, $del);
        $this->assertFalse($admin->fresh()->hasRole('branch-admin'));
        $this->assertFalse($this->svc->isBranchAdmin($admin->fresh()));
    }

    public function test_cannot_remove_last_super_admin(): void
    {
        $this->expectException(ValidationException::class);
        $this->svc->assertNotLastSuperAdmin($this->super);
    }

    public function test_second_super_admin_allows_removal(): void
    {
        $other = User::factory()->create(['status' => 'active']);
        $other->assignRole('super-admin');
        $this->svc->assertNotLastSuperAdmin($this->super);
        $this->addToAssertionCount(1);
    }

    public function test_user_without_branch_has_no_branch_admin_authority(): void
    {
        $u = User::factory()->create(['status' => 'active', 'branch_id' => null]);
        $u->assignRole('branch-admin'); // role alone is not authority
        $this->assertFalse($this->svc->isBranchAdmin($u));
        $target = User::factory()->create(['status' => 'active', 'branch_id' => $this->dubai->id]);
        $this->expectException(ValidationException::class);
        $this->svc->assertCanActOn($u, $target);
    }

    public function test_seeded_roles_exist_and_reseeding_idempotent(): void
    {
        foreach (['branch-admin', 'Branch Manager', 'Auditor', 'super-admin'] as $r) {
            $this->assertTrue(Role::where('name', $r)->exists(), "missing role {$r}");
        }
        $count = Role::count();
        $this->seed(IdentityDatabaseSeeder::class);
        $this->assertSame($count, Role::count());
    }

    public function test_http_branch_admin_cannot_reach_branch_admins_console(): void
    {
        $admin = $this->branchAdmin($this->dubai);
        $this->actingAs($admin)->get(route('identity.admin.branch-admins'))->assertForbidden();
    }

    public function test_http_super_admin_reaches_console(): void
    {
        $this->actingAs($this->super)->get(route('identity.admin.dashboard'))->assertOk();
        $this->actingAs($this->super)->get(route('identity.admin.branch-admins'))->assertOk();
    }

    public function test_http_manipulated_branch_id_on_user_index_is_scoped(): void
    {
        $admin = $this->branchAdmin($this->dubai);
        User::factory()->create(['status' => 'active', 'branch_id' => $this->tz->id, 'name' => 'TZUSER_XYZ']);
        $res = $this->actingAs($admin)->get(route('identity.users.index'));
        $res->assertOk()->assertDontSee('TZUSER_XYZ');
    }
}
