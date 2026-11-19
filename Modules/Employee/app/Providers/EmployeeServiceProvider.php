<?php

namespace Modules\Employee\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class EmployeeServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Employee';

    protected string $nameLower = 'employee';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        $this->loadViewsFrom(module_path($this->name, 'resources/views'), $this->nameLower);
        $this->registerNavigation();
    }

    private function registerNavigation(): void
    {
        $nav = $this->app->make(\App\Services\Navigation\NavigationRegistry::class);
        $icon = 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z';

        $items = [
            ['Dashboard', 'employee.dashboard', ['employee.view', 'employee.view_all'], 10],
            ['Directory', 'employee.index', ['employee.view', 'employee.view_all'], 20],
            ['Positions & Grades', 'employee.structure.index', ['employee.position.manage', 'employee.grade.manage'], 30],
            ['Transfers', 'employee.transfers.index', ['employee.transfer.request', 'employee.transfer.approve'], 40],
            ['Promotions & Changes', 'employee.changes.index', ['employee.promotion.request', 'employee.promotion.approve'], 50],
            ['Onboarding', 'employee.onboarding.index', ['employee.onboarding.manage'], 60],
            ['Offboarding', 'employee.offboarding.index', ['employee.offboarding.manage'], 70],
            ['My Employment', 'employee.me', [], 5],
        ];

        foreach ($items as [$label, $route, $permissions, $order]) {
            $nav->register(new \App\Services\Navigation\NavItem(
                module: 'Employee',
                label: $label,
                icon: $icon,
                route: $route,
                permission: $permissions ?: null,
                group: 'Workforce',
                order: $order,
            ));
        }
    }
}
