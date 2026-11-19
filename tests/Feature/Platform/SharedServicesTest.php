<?php

namespace Tests\Feature\Platform;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use App\Services\Navigation\NavItem;
use App\Services\Navigation\NavigationRegistry;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_service_get_set_and_group(): void
    {
        $settings = app(SettingsService::class);

        $settings->set('company.name', 'Xerin Group', 'branding');
        $settings->set('company.employees', '42', 'branding', 'int');

        $this->assertSame('Xerin Group', $settings->get('company.name'));
        $this->assertSame(42, $settings->get('company.employees'));
        $this->assertSame('default', $settings->get('missing.key', 'default'));
        $this->assertEquals('Xerin Group', $settings->group('branding')['company.name']);
    }

    public function test_settings_can_store_encrypted_values(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('smtp.pass', 's3cret', 'mail', 'string', encrypt: true);

        $this->assertSame('s3cret', $settings->get('smtp.pass'));
        $this->assertNotSame('s3cret', \App\Models\SystemSetting::where('key', 'smtp.pass')->value('value'));
    }

    public function test_audit_service_captures_context(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $log = app(AuditService::class)->log('user.updated', $user, ['name' => 'old'], ['name' => 'new']);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'user_id' => $user->id,
            'action' => 'user.updated',
            'resource_type' => User::class,
        ]);
        $this->assertEquals(['name' => 'old'], $log->old_values);
        $this->assertEquals(['name' => 'new'], $log->new_values);
    }

    public function test_navigation_registry_filters_by_permission(): void
    {
        $nav = new NavigationRegistry();
        $nav->register(new NavItem(module: 'Core', label: 'Dashboard', icon: 'i', route: 'dashboard'));
        $nav->register(new NavItem(module: 'Core', label: 'Secret', icon: 'i', url: '/secret', permission: 'nope.never'));

        $user = User::factory()->create();
        $this->actingAs($user);

        $visible = $nav->visible();
        $labels = collect($visible)->flatten(1)->pluck('label');

        $this->assertContains('Dashboard', $labels);
        $this->assertNotContains('Secret', $labels);
    }
}
