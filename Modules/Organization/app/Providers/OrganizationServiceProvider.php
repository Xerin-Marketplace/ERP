<?php

namespace Modules\Organization\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class OrganizationServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Organization';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'organization';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        $this->loadViewsFrom(module_path($this->name, 'resources/views'), $this->nameLower);

        \Illuminate\Support\Facades\Gate::policy(
            \Modules\Organization\Models\Branch::class,
            \Modules\Organization\Policies\BranchPolicy::class
        );
        \Illuminate\Support\Facades\Gate::policy(
            \Modules\Organization\Models\Department::class,
            \Modules\Organization\Policies\DepartmentPolicy::class
        );
        \Illuminate\Support\Facades\Gate::policy(
            \Modules\Employee\Models\JobPosition::class,
            \Modules\Organization\Policies\PositionPolicy::class
        );

        $this->registerNavigation();
    }

    /**
     * Organization module navigation — permission-gated entries.
     */
    private function registerNavigation(): void
    {
        $nav = $this->app->make(\App\Services\Navigation\NavigationRegistry::class);
        $icon = 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z';

        $items = [
            ['Overview', 'organization.index', 'organization.dashboard.view', 10],
            ['Companies', 'organization.companies.index', 'organization.company.view', 11],
            ['Branches', 'organization.branches.index', 'organization.branches.view', 12],
            ['Departments', 'organization.departments.index', 'organization.departments.view', 13],
            ['Positions', 'organization.positions.index', 'organization.positions.view', 14],
            ['Locations', 'organization.locations.index', 'organization.locations.view', 15],
            ['Structure', 'organization.structure', 'organization.structure.view', 16],
            ['Audit History', 'organization.audit', 'organization.audit.view', 17],
            ['Org Settings', 'organization.settings', 'organization.settings.manage', 18],
        ];

        $children = [];
        foreach ($items as [$label, $route, $permission, $order]) {
            $children[] = new \App\Services\Navigation\NavItem(
                module: 'Organization', label: $label, icon: '',
                route: $route, permission: $permission, group: 'Organization', order: $order,
            );
        }

        $nav->register(new \App\Services\Navigation\NavItem(
            module: 'Organization', label: 'Organization', icon: $icon,
            group: 'Organization', order: 10, children: $children,
        ));
    }

    /**
     * Define module schedules.
     * 
     * @param $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
