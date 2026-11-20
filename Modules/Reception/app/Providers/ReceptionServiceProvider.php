<?php

namespace Modules\Reception\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ReceptionServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Reception';

    protected string $nameLower = 'reception';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();
        $this->registerNavigation();
        $this->commands([\Modules\Reception\Console\Commands\SuspendExpiredExternalAccounts::class]);
        $this->app->booted(function () {
            $this->app->make(Schedule::class)
                ->command('reception:suspend-expired')->dailyAt('01:00');
        });
    }

    private function registerNavigation(): void
    {
        $nav = $this->app->make(\App\Services\Navigation\NavigationRegistry::class);
        $icon = 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3v-6h6v6h3a1 1 0 001-1V10';

        $items = [
            ['Reception', 'reception.dashboard', ['reception.dashboard.view'], 10],
            ['Attendance', 'reception.attendance.index', ['reception.attendance.view'], 20],
            ['IT Tickets', 'reception.tickets.index', ['reception.tickets.view', 'reception.tickets.create'], 30],
            ['New Ticket', 'reception.tickets.create', ['reception.tickets.create'], 35],
            ['Directory', 'reception.directory', ['reception.employees.view_directory'], 40],
            ['IT Technicians', 'reception.technicians', ['reception.it_availability.view'], 50],
            ['Access Requests', 'reception.access.requests.index', ['reception.access.view'], 60],
            ['Access Dashboard', 'reception.access.dashboard', ['reception.access.view'], 61],
            ['Approval Inbox', 'reception.access.approvals.inbox', null, 62],
            ['Provisioning', 'reception.access.provisioning.queue', ['reception.access.provision'], 70],
            ['Workflows', 'reception.access.workflows.index', ['reception.access.workflows.manage'], 80],
        ];

        $children = [];
        foreach ($items as [$label, $route, $permission, $order]) {
            $children[] = new \App\Services\Navigation\NavItem(
                module: 'Reception', label: $label, icon: '',
                route: $route, permission: $permission, group: 'Reception', order: $order,
            );
        }

        $nav->register(new \App\Services\Navigation\NavItem(
            module: 'Reception', label: 'Reception', icon: $icon,
            group: 'Reception', order: 10, children: $children,
        ));
    }
}
