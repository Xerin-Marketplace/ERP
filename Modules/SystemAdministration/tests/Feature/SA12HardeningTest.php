<?php

namespace Modules\SystemAdministration\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder;
use Modules\SystemAdministration\Database\Seeders\SystemAdministrationDatabaseSeeder;
use Modules\SystemAdministration\Models\SettingChangeRequest;
use Modules\SystemAdministration\Models\SystemIntegration;
use Modules\SystemAdministration\Services\{IntegrationTester, MfaService, ScopedSettingsResolver, SettingChangeService};
use Tests\TestCase;

class SA12HardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $admin2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(OrganizationDatabaseSeeder::class);
        $this->seed(SystemAdministrationDatabaseSeeder::class);
        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('super-admin');
        $this->admin2 = User::factory()->create(['status' => 'active']);
        $this->admin2->assignRole('super-admin');
    }

    /* ---- SA.12.1 scoped settings ---- */
    public function test_company_override_does_not_leak(): void
    {
        $svc = app(ScopedSettingsResolver::class);
        // system.locale allows company+user scopes
        $svc->set('system.locale', 'company', 1, 'sw', $this->admin);
        $inCompany1 = $svc->resolve('system.locale', $this->admin, ['company_id' => 1]);
        $inCompany2 = $svc->resolve('system.locale', $this->admin, ['company_id' => 2]);
        $this->assertSame('sw', $inCompany1['value']);
        $this->assertSame('en', $inCompany2['value']); // global default
    }

    public function test_user_preference_wins_when_allowed(): void
    {
        $svc = app(ScopedSettingsResolver::class);
        $svc->set('system.locale', 'company', 1, 'sw', $this->admin);
        $svc->set('system.locale', 'user', $this->admin->id, 'en', $this->admin);
        $r = $svc->resolve('system.locale', $this->admin, ['company_id' => 1]);
        $this->assertSame('en', $r['value']);
        $this->assertStringStartsWith('user:', $r['source']);
    }

    public function test_protected_setting_rejects_scope_override(): void
    {
        $svc = app(ScopedSettingsResolver::class);
        $this->expectException(ValidationException::class);
        $svc->set('rules.auth.max_login_attempts', 'branch', 1, '99', $this->admin);
    }

    public function test_unsupported_scope_rejected(): void
    {
        $svc = app(ScopedSettingsResolver::class);
        $this->expectException(ValidationException::class);
        $svc->set('system.timezone', 'branch', 1, 'UTC', $this->admin); // no allowed_scopes
    }

    /* ---- SA.12.2 approval governance ---- */
    public function test_high_risk_goes_pending_and_self_approve_blocked(): void
    {
        $svc = app(SettingChangeService::class);
        $cr = $svc->requestChange('rules.auth.max_login_attempts', '7', $this->admin, 'tighten security');
        $this->assertSame('pending', $cr->status);
        $this->expectException(ValidationException::class);
        $svc->approve($cr, $this->admin);
    }

    public function test_second_approver_publishes_and_versions(): void
    {
        $svc = app(SettingChangeService::class);
        $cr = $svc->requestChange('rules.auth.max_login_attempts', '7', $this->admin, 'tighten');
        $cr = $svc->approve($cr, $this->admin2, 'ok');
        $this->assertSame('published', $cr->status);
        $this->assertSame('7', \App\Models\SystemSetting::where('key','rules.auth.max_login_attempts')->value('value'));
        $this->assertDatabaseHas('system_setting_versions', ['key' => 'rules.auth.max_login_attempts', 'new_value' => '7']);
    }

    public function test_low_risk_publishes_immediately(): void
    {
        $cr = app(SettingChangeService::class)->requestChange('system.locale', 'sw', $this->admin, 'switch default');
        $this->assertSame('published', $cr->status);
    }

    public function test_stale_base_version_rejected(): void
    {
        $svc = app(SettingChangeService::class);
        $cr = $svc->requestChange('rules.auth.max_login_attempts', '7', $this->admin, 'r');
        // someone else changes the setting → version count grows
        \Modules\SystemAdministration\Models\SettingVersion::create(['key'=>'rules.auth.max_login_attempts','group'=>'x','old_value'=>'5','new_value'=>'6','risk'=>'high','changed_by'=>$this->admin2->id]);
        try { $svc->approve($cr->fresh(), $this->admin2); $this->fail('should throw'); }
        catch (ValidationException) { $this->assertSame('stale', $cr->fresh()->status); }
    }

    /* ---- SA.12.3 real integration testing ---- */
    public function test_invalid_integration_is_not_marked_successful(): void
    {
        $i = SystemIntegration::create(['key' => 'smtp_test', 'name' => 'SMTP', 'driver' => 'smtp',
            'is_enabled' => true]);
        $i->setConfig(['smtp_host' => '127.0.0.1', 'port' => 1]);
        $res = app(IntegrationTester::class)->test($i, $this->admin->id);
        $this->assertNotSame('successful', $res->status);
        $this->assertContains($res->status, ['unavailable','timeout','auth_failed','config_invalid']);
        $this->assertDatabaseHas('integration_test_results', ['integration_id' => $i->id]);
    }

    public function test_no_host_is_config_invalid(): void
    {
        $i = SystemIntegration::create(['key' => 'x', 'name' => 'X', 'driver' => 'api',
            'is_enabled' => true]);
        $res = app(IntegrationTester::class)->test($i);
        $this->assertSame('config_invalid', $res->status);
    }

    /* ---- SA.12.4 MFA ---- */
    public function test_totp_enroll_confirm_challenge(): void
    {
        $svc = app(MfaService::class);
        $e = $svc->beginEnrollment($this->admin);
        $this->assertNotEmpty($e['provisioning_uri']);
        $mfa = $svc->mfa($this->admin);
        $this->assertFalse($mfa->enabled); // not enabled until verified
        // generate current TOTP using the service's own algorithm
        $code = (fn() => (new \ReflectionMethod(MfaService::class, 'totp'))->invoke($svc, $mfa->secret, (int) floor(time()/30)))();
        $recovery = $svc->confirmEnrollment($this->admin, $code);
        $this->assertTrue($svc->mfa($this->admin)->enabled);
        $this->assertCount(8, $recovery);
        // wrong code rejected
        $this->assertFalse($svc->challenge($this->admin, '000000'));
        // recovery code works once
        $this->assertTrue($svc->challenge($this->admin, $recovery[0]));
        $this->assertFalse($svc->challenge($this->admin, $recovery[0])); // reused → rejected
    }

    public function test_super_admin_always_requires_mfa(): void
    {
        $this->assertTrue(app(MfaService::class)->isRequired($this->admin));
    }

    public function test_wrong_totp_rejected_before_activation(): void
    {
        $svc = app(MfaService::class);
        $svc->beginEnrollment($this->admin);
        $this->expectException(ValidationException::class);
        $svc->confirmEnrollment($this->admin, '000000');
    }
}
