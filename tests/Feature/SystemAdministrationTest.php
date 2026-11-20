<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SystemAdministration\Models\FeatureFlag;
use Modules\SystemAdministration\Models\SettingVersion;
use Modules\SystemAdministration\Services\FeatureFlagService;
use Modules\SystemAdministration\Services\SettingCatalog;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SystemAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Modules\SystemAdministration\Database\Seeders\SystemAdministrationDatabaseSeeder::class);
        $this->admin = User::factory()->create(['email' => 'sa@test.dev', 'status' => 'active']);
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web'])
            ->givePermissionTo(\Spatie\Permission\Models\Permission::pluck('name')));
    }

    public function test_unauthorized_user_cannot_access_control_center(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user)->get('/system-admin')->assertForbidden();
        $this->actingAs($user)->get('/system-admin/rules')->assertForbidden();
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/system-admin')->assertRedirect('/login');
    }

    public function test_super_admin_can_access_all_admin_areas(): void
    {
        foreach (['/system-admin', '/system-admin/branding', '/system-admin/settings/General',
                  '/system-admin/rules', '/system-admin/modules', '/system-admin/audit',
                  '/system-admin/monitoring', '/system-admin/maintenance'] as $path) {
            $this->actingAs($this->admin)->get($path)->assertOk();
        }
    }

    public function test_settings_update_persists_and_is_versioned(): void
    {
        $catalog = app(SettingCatalog::class);
        $changed = $catalog->updateMany(['rules.password.min_length' => 14], 'hardening');

        $this->assertContains('rules.password.min_length', $changed);
        $this->assertSame(14, $catalog->get('rules.password.min_length'));
        $this->assertDatabaseHas('system_setting_versions', ['key' => 'rules.password.min_length', 'new_value' => '14', 'reason' => 'hardening']);
    }

    public function test_invalid_setting_value_is_rejected(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(SettingCatalog::class)->updateMany(['rules.password.min_length' => 2]);
    }

    public function test_locked_rule_cannot_be_changed(): void
    {
        app(SettingCatalog::class)->updateMany(['rules.registration.require_it_approval' => false]);
        $this->assertTrue(app(SettingCatalog::class)->get('rules.registration.require_it_approval'));
    }

    public function test_feature_flag_evaluation(): void
    {
        $flags = app(FeatureFlagService::class);
        FeatureFlag::create(['key' => 'beta_x', 'label' => 'Beta', 'is_enabled' => true]);
        $this->assertTrue($flags->enabled('beta_x', $this->admin));

        FeatureFlag::create(['key' => 'future_x', 'label' => 'F', 'is_enabled' => true, 'starts_at' => now()->addDay()]);
        $this->assertFalse($flags->enabled('future_x', $this->admin));
    }

    public function test_protected_module_cannot_be_disabled(): void
    {
        $this->actingAs($this->admin)->post('/system-admin/modules/Core/toggle')->assertRedirect();
        $this->assertTrue(\Module::find('Core')->isEnabled());
    }

    public function test_integration_secret_is_encrypted_and_masked(): void
    {
        $i = \Modules\SystemAdministration\Models\SystemIntegration::create([
            'key' => 'sms-test', 'name' => 'SMS', 'driver' => 'sms',
        ]);
        $i->setConfig(['endpoint' => 'https://x.test', 'api_secret' => 'shh']);
        $i->save();

        $this->assertStringNotContainsString('shh', $i->config);
        $this->assertSame('••••••••', $i->maskedConfig()['api_secret']);
        $this->assertSame('shh', $i->configArray()['api_secret']);
    }
}
