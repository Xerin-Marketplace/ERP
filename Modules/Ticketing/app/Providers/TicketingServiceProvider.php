<?php

namespace Modules\Ticketing\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Nwidart\Modules\Support\ModuleServiceProvider;

class TicketingServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Ticketing';
    protected string $nameLower = 'ticketing';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        $this->loadViewsFrom(module_path($this->name, 'resources/views'), $this->nameLower);

        $this->commands([\Modules\Ticketing\Console\TicketSlaScan::class]);
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('tickets:sla-scan')->everyFiveMinutes()->withoutOverlapping();
        });

        $this->registerNavigation();
    }

    private function registerNavigation(): void
    {
        $nav = $this->app->make(\App\Services\Navigation\NavigationRegistry::class);
        $icon = 'M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z';

        $items = [
            ['Dashboard', 'ticketing.dashboard', null, 10],
            ['My Tickets', 'ticketing.my', ['ticket.create'], 20],
            ['All Tickets', 'ticketing.index', ['ticket.view', 'ticket.view_all'], 30],
            ['Service Catalog', 'ticketing.catalog', null, 40],
            ['Tasks', 'ticketing.tasks.index', ['task.view', 'ticket.view', 'ticket.view_all'], 50],
            ['Approvals', 'ticketing.approvals.index', ['ticket.approve'], 60],
            ['Knowledge Base', 'ticketing.kb.index', null, 70],
            ['Teams & SLA', 'ticketing.admin.teams', ['ticket.view_all'], 80],
            ['Reports', 'ticketing.admin.reports', ['ticket_reports.view'], 90],
        ];

        foreach ($items as [$label, $route, $permission, $order]) {
            $nav->register(new \App\Services\Navigation\NavItem(
                module: 'Ticketing', label: $label, icon: $icon,
                route: $route, permission: $permission, group: 'Service Desk', order: $order,
            ));
        }
    }
}
