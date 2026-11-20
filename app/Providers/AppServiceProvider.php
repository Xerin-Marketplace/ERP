<?php

namespace App\Providers;

use App\Services\AuditService;
use App\Services\FileService;
use App\Services\Navigation\NavItem;
use App\Services\Navigation\NavigationRegistry;
use App\Services\SettingsService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NavigationRegistry::class);
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(AuditService::class);
        $this->app->singleton(FileService::class);
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            [$limit, $minutes] = explode(',', config('erp.api.rate_limit', '60,1'));

            return Limit::perMinutes((int) $minutes, (int) $limit)
                ->by($request->user()?->id ?: $request->ip());
        });

        // Centralized auth event logging
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            fn ($event) => $this->app->make(AuditService::class)->log('auth.login', $event->user)
        );

        $this->applySystemSettings();
        $this->registerCoreNavigation();
    }

    /**
     * Push persisted system settings (System > Settings) into runtime config.
     * Wrapped in try/catch — the DB is unavailable during early migrations.
     */
    private function applySystemSettings(): void
    {
        try {
            $settings = $this->app->make(SettingsService::class);

            foreach (['app_name' => 'app.name', 'timezone' => 'app.timezone', 'locale' => 'app.locale', 'session_lifetime' => 'session.lifetime'] as $key => $configKey) {
                $value = $settings->get("system.{$key}");
                if ($value !== null && $value !== '') {
                    config([$configKey => $value]);
                }
            }
        } catch (\Throwable) {
            // DB not ready (migrations, fresh install) — config defaults apply.
        }
    }

    /**
     * Core platform nav items. Modules register their own items
     * in their service provider via Navigation::register(new NavItem(...)).
     */
    private function registerCoreNavigation(): void
    {
        $nav = $this->app->make(NavigationRegistry::class);

        $nav->register(new NavItem(
            module: 'Core',
            label: 'Dashboard',
            icon: 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
            route: 'dashboard',
            group: 'Menu',
            order: 1,
        ));

        // Executive Command Center — group-wide BI for CEO / Super Admin.
        $nav->register(new NavItem(
            module: 'Core',
            label: 'Executive',
            icon: 'M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941',
            group: 'Executive',
            order: 5,
            children: [
                new NavItem(module: 'Core', label: 'Command Center', icon: '', route: 'executive.dashboard', permission: 'executive.command.view', group: 'Executive', order: 1),
                new NavItem(module: 'Core', label: 'Financial Intelligence', icon: '', route: 'executive.financial', permission: 'executive.command.view', group: 'Executive', order: 5),
                new NavItem(module: 'Core', label: 'Sales Intelligence', icon: '', route: 'executive.sales', permission: 'executive.command.view', group: 'Executive', order: 10),
                new NavItem(module: 'Core', label: 'Branch Performance', icon: '', route: 'executive.branches', permission: 'executive.command.view', group: 'Executive', order: 15),
                new NavItem(module: 'Core', label: 'Department Performance', icon: '', route: 'executive.departments', permission: 'executive.command.view', group: 'Executive', order: 20),
                new NavItem(module: 'Core', label: 'Employee Intelligence', icon: '', route: 'executive.employees', permission: 'executive.command.view', group: 'Executive', order: 25),
                new NavItem(module: 'Core', label: 'Tasks & Workload', icon: '', route: 'executive.tasks', permission: 'executive.command.view', group: 'Executive', order: 30),
                new NavItem(module: 'Core', label: 'Tender Pipeline', icon: '', route: 'executive.tenders', permission: 'executive.command.view', group: 'Executive', order: 35),
                new NavItem(module: 'Core', label: 'Activity & Audit', icon: '', route: 'executive.activity', permission: 'executive.command.view', group: 'Executive', order: 40),
            ]
        ));

        // System menu — single collapsible parent whose children are gated
        // by their own permissions (admin sees the full set).
        $nav->register(new NavItem(
            module: 'Core',
            label: 'System',
            icon: 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281zM15 12a3 3 0 11-6 0 3 3 0 016 0z',
            group: 'Administration',
            order: 90,
            children: [
                new NavItem(module: 'SystemAdministration', label: 'Control Center', icon: '', route: 'systemadmin.dashboard', permission: 'sysadmin.dashboard.view', group: 'Administration', order: 5),
                new NavItem(module: 'Core', label: 'System Overview', icon: '', route: 'admin.system', permission: 'system.view', group: 'Administration', order: 10),
                new NavItem(module: 'Tender', label: 'Tenders', icon: '', route: 'tender.dashboard', permission: 'tenders.view', group: 'Operations', order: 60),
                new NavItem(module: 'Identity', label: 'Admin Console', icon: '', route: 'identity.admin.dashboard', permission: 'admin.console.view', group: 'Administration', order: 18),
                new NavItem(module: 'Identity', label: 'User Management', icon: '', route: 'identity.users.index', permission: 'users.view', group: 'Administration', order: 20),
                new NavItem(module: 'Identity', label: 'Roles & Permissions', icon: '', route: 'identity.roles.index', permission: 'roles.view', group: 'Administration', order: 30),
                new NavItem(module: 'Identity', label: 'Permission Matrix', icon: '', route: 'identity.permissions.matrix', permission: 'permissions.view', group: 'Administration', order: 32),
                new NavItem(module: 'Identity', label: 'Pending Invitations', icon: '', route: 'identity.invitations.index', permission: 'users.invite', group: 'Administration', order: 34),
                new NavItem(module: 'Identity', label: 'Access Control', icon: '', route: 'identity.access.index', permission: 'access.audit.view', group: 'Administration', order: 36),
                new NavItem(module: 'Identity', label: 'Registrations', icon: '', route: 'identity.registrations.index', permission: 'registration.view', group: 'Administration', order: 40),
                new NavItem(module: 'SystemAdministration', label: 'Branding', icon: '', route: 'systemadmin.branding', permission: 'sysadmin.branding.manage', group: 'Administration', order: 50),
                new NavItem(module: 'SystemAdministration', label: 'General Settings', icon: '', route: 'systemadmin.settings', permission: 'sysadmin.settings.manage', group: 'Administration', order: 55),
                new NavItem(module: 'SystemAdministration', label: 'System Rules', icon: '', route: 'systemadmin.rules', permission: 'sysadmin.rules.manage', group: 'Administration', order: 60),
                new NavItem(module: 'SystemAdministration', label: 'Module Management', icon: '', route: 'systemadmin.modules', permission: 'sysadmin.modules.manage', group: 'Administration', order: 65),
                new NavItem(module: 'SystemAdministration', label: 'Command Center', icon: '', route: 'systemadmin.commands.index', permission: 'sysadmin.commands.view', group: 'Administration', order: 67),
                new NavItem(module: 'SystemAdministration', label: 'Navigation', icon: '', route: 'systemadmin.navigation', permission: 'sysadmin.navigation.manage', group: 'Administration', order: 70),
                new NavItem(module: 'SystemAdministration', label: 'Feature Flags', icon: '', route: 'systemadmin.flags', permission: 'sysadmin.flags.manage', group: 'Administration', order: 75),
                new NavItem(module: 'SystemAdministration', label: 'Announcements', icon: '', route: 'systemadmin.announcements', permission: 'sysadmin.announcements.manage', group: 'Administration', order: 80),
                new NavItem(module: 'SystemAdministration', label: 'Integrations', icon: '', route: 'systemadmin.integrations', permission: 'sysadmin.integrations.manage', group: 'Administration', order: 85),
                new NavItem(module: 'SystemAdministration', label: 'Security Center', icon: '', route: 'systemadmin.security', permission: 'sysadmin.security.manage', group: 'Administration', order: 90),
                new NavItem(module: 'SystemAdministration', label: 'Audit Logs', icon: '', route: 'systemadmin.audit', permission: 'sysadmin.audit.view', group: 'Administration', order: 95),
                new NavItem(module: 'SystemAdministration', label: 'Maintenance', icon: '', route: 'systemadmin.maintenance', permission: 'sysadmin.maintenance.manage', group: 'Administration', order: 100),
                new NavItem(module: 'SystemAdministration', label: 'Monitoring', icon: '', route: 'systemadmin.monitoring', permission: 'sysadmin.monitoring.view', group: 'Administration', order: 105),
                new NavItem(module: 'SystemAdministration', label: 'Localization', icon: '', route: 'systemadmin.localization', permission: 'sysadmin.localization.manage', group: 'Administration', order: 110),
                new NavItem(module: 'SystemAdministration', label: 'Backups', icon: '', route: 'systemadmin.backups', permission: 'sysadmin.backups.view', group: 'Administration', order: 115),
                new NavItem(module: 'Core', label: 'API Documentation', icon: '', route: 'api.docs', permission: 'system.view', group: 'Administration', order: 120),
            ],
        ));
    }
}
