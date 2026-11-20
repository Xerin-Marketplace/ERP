<?php

namespace Modules\SystemAdministration\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\SystemAdministration\Database\Seeders\SystemAdministrationDatabaseSeeder;
use Modules\SystemAdministration\Models\CommandExecution;
use Modules\SystemAdministration\Services\CommandExecutor;
use Modules\SystemAdministration\Services\CommandRegistry;
use Tests\TestCase;

class CommandCenterTest extends TestCase
{
    use RefreshDatabase;

    private User $super;
    private CommandExecutor $exec;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SystemAdministrationDatabaseSeeder::class);
        $this->super = User::factory()->create(['status' => 'active']);
        $this->super->assignRole('super-admin');
        $this->exec = app(CommandExecutor::class);
    }

    public function test_registry_has_real_commands(): void
    {
        $defs = CommandRegistry::definitions();
        $this->assertGreaterThan(10, count($defs));
        foreach ($defs as $id => $d) {
            $this->assertIsCallable($d['handler'], "{$id} has no real handler");
        }
    }

    public function test_low_risk_executes_immediately(): void
    {
        $e = $this->exec->submit('system.info', [], $this->super);
        $this->assertSame('completed', $e->status);
        $this->assertNotNull($e->output);
        $this->assertNotNull($e->duration_ms);
    }

    public function test_invalid_params_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->exec->submit('users.suspend', ['user_id' => 999999], $this->super, 'test');
    }

    public function test_high_risk_requires_reason_and_approval(): void
    {
        $this->expectException(ValidationException::class);
        $this->exec->submit('users.suspend', ['user_id' => $this->super->id], $this->super);
    }

    public function test_high_risk_goes_pending_then_approval_runs(): void
    {
        $target = User::factory()->create(['status' => 'active']);
        $e = $this->exec->submit('users.suspend', ['user_id' => $target->id], $this->super, 'policy violation');
        $this->assertSame('pending_approval', $e->status);
        // requester cannot self-approve
        $this->expectException(ValidationException::class);
        $this->exec->approve($e, $this->super);
    }

    public function test_second_admin_approves_and_command_runs(): void
    {
        $target = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $other->assignRole('super-admin');
        $e = $this->exec->submit('users.suspend', ['user_id' => $target->id], $this->super, 'test');
        $e = $this->exec->approve($e, $other, 'ok');
        $this->assertSame('completed', $e->status);
        $this->assertSame('suspended', $target->fresh()->status->value ?? $target->fresh()->status);
    }

    public function test_duplicate_pending_blocked(): void
    {
        $t = User::factory()->create(['status' => 'active']);
        $this->exec->submit('users.suspend', ['user_id' => $t->id], $this->super, 'r1');
        $this->expectException(ValidationException::class);
        $this->exec->submit('users.suspend', ['user_id' => $t->id], $this->super, 'r2');
    }

    public function test_secret_params_redacted(): void
    {
        $e = $this->exec->submit('system.info', ['api_secret' => 'x'], $this->super);
        $this->assertSame('•••', $e->params['api_secret']);
    }

    public function test_http_forbidden_without_permission(): void
    {
        $plain = User::factory()->create(['status' => 'active']);
        $this->actingAs($plain)->get(route('systemadmin.commands.index'))->assertForbidden();
        $this->actingAs($plain)->post(route('systemadmin.commands.execute', 'system.info'))->assertForbidden();
    }

    public function test_http_super_admin_executes(): void
    {
        $r = $this->actingAs($this->super)->post(route('systemadmin.commands.execute', 'system.info'));
        $r->assertSessionHas('execution_id');
        $this->assertSame(1, CommandExecution::count());
    }

    public function test_artisan_cli_runs(): void
    {
        $this->artisan('xerin:command', ['id' => 'system.info', '--format' => 'json'])
            ->assertExitCode(0);
        $this->artisan('xerin:command', ['id' => 'does.not.exist'])->assertExitCode(2);
    }
}
