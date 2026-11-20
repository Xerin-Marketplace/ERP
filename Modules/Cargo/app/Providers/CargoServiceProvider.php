<?php
namespace Modules\Cargo\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class CargoServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Cargo';
    protected string $nameLower = 'cargo';
    protected array $providers = [RouteServiceProvider::class];

    public function boot(): void
    {
        parent::boot();
        $nav = $this->app->make(\App\Services\Navigation\NavigationRegistry::class);
        $icon = 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z';
        $items = [
            ['Cargo Dashboard', 'cargo.dashboard', ['cargo.dashboard.view'], 10],
            ['Pre-Alerts', 'cargo.pre-alerts.index', ['cargo.pre_alerts.view'], 20],
            ['Receiving', 'cargo.receivings.index', ['cargo.receiving.view'], 30],
            ['Packages', 'cargo.packages.index', ['cargo.packages.view'], 40],
            ['Warehouses', 'cargo.warehouses.index', ['warehouse.view'], 50],
            ['Incidents', 'cargo.incidents.index', ['cargo.incidents.view'], 60],
        ];
        $children = [];
        foreach ($items as [$label, $route, $perm, $order]) {
            $children[] = new \App\Services\Navigation\NavItem(
                module: 'Cargo', label: $label, icon: '', route: $route,
                permission: $perm, group: 'Cargo Operations', order: $order);
        }
        $nav->register(new \App\Services\Navigation\NavItem(
            module: 'Cargo', label: 'Cargo Operations', icon: $icon,
            group: 'Cargo Operations', order: 30, children: $children));
    }
}
