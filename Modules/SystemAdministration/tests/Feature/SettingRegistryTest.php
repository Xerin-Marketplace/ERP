<?php

namespace Modules\SystemAdministration\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SystemAdministration\Services\SettingCatalog;
use Modules\SystemAdministration\Services\SettingRegistry;
use Tests\TestCase;

class SettingRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        SettingRegistry::flush();
        parent::tearDown();
    }

    public function test_module_self_registers_settings_into_catalog(): void
    {
        SettingRegistry::register('cargo', [
            'cargo.auto_shelf' => ['group' => 'Cargo', 'label' => 'Auto shelf', 'type' => 'bool', 'default' => true, 'rules' => 'boolean', 'risk' => 'low'],
        ]);
        $defs = SettingCatalog::definitions();
        $this->assertArrayHasKey('cargo.auto_shelf', $defs);
        $this->assertSame('cargo', $defs['cargo.auto_shelf']['module']);
    }

    public function test_registration_rejects_foreign_namespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SettingRegistry::register('cargo', [
            'finance.sneaky' => ['group' => 'X', 'label' => 'x', 'type' => 'bool', 'default' => false, 'rules' => 'boolean', 'risk' => 'low'],
        ]);
    }

    public function test_registration_rejects_incomplete_definition(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SettingRegistry::register('cargo', ['cargo.bad' => ['group' => 'X']]);
    }

    public function test_registry_visible_in_modules_metadata(): void
    {
        SettingRegistry::register('procurement', [
            'procurement.rfq_required' => ['group' => 'Proc', 'label' => 'x', 'type' => 'bool', 'default' => true, 'rules' => 'boolean', 'risk' => 'medium'],
        ]);
        $this->assertSame('procurement', collect(SettingRegistry::modules())->first()['module']);
    }

    public function test_core_definitions_still_present(): void
    {
        $defs = SettingCatalog::definitions();
        $this->assertArrayHasKey('system.timezone', $defs);
        $this->assertArrayHasKey('rules.password.min_length', $defs);
    }
}
