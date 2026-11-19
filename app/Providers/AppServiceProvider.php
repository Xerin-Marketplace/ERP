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

        // System menu — single collapsible parent whose children are gated
        // by their own permissions (admin sees the full set).
        $nav->register(new NavItem(
            module: 'Core',
            label: 'System',
            icon: 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281zM15 12a3 3 0 11-6 0 3 3 0 016 0z',
            group: 'Administration',
            order: 90,
            children: [
                new NavItem(
                    module: 'Core',
                    label: 'System Overview',
                    icon: '',
                    route: 'admin.system',
                    permission: 'system.view',
                    group: 'Administration',
                    order: 10,
                ),
                new NavItem(
                    module: 'Identity',
                    label: 'User Management',
                    icon: '',
                    route: 'identity.users.index',
                    permission: 'user.view',
                    group: 'Administration',
                    order: 20,
                ),
                new NavItem(
                    module: 'Identity',
                    label: 'Roles & Permissions',
                    icon: '',
                    route: 'identity.roles.index',
                    permission: 'role.view',
                    group: 'Administration',
                    order: 30,
                ),
                new NavItem(
                    module: 'Identity',
                    label: 'Registrations',
                    icon: '',
                    route: 'identity.registrations.index',
                    permission: 'registration.view',
                    group: 'Administration',
                    order: 40,
                ),
                new NavItem(
                    module: 'Organization',
                    label: 'Audit Log',
                    icon: '',
                    route: 'organization.audit',
                    permission: 'organization.audit.view',
                    group: 'Administration',
                    order: 50,
                ),
                new NavItem(
                    module: 'Core',
                    label: 'Settings',
                    icon: '',
                    route: 'admin.settings',
                    permission: 'settings.view',
                    group: 'Administration',
                    order: 60,
                ),
                new NavItem(
                    module: 'Core',
                    label: 'API Documentation',
                    icon: '',
                    route: 'api.docs',
                    permission: 'system.view',
                    group: 'Administration',
                    order: 70,
                ),
            ],
        ));
    }
}
