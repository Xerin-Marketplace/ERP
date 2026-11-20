<?php

namespace Modules\Commercial\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class CommercialServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Commercial';
    protected string $nameLower = 'commercial';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();
        $this->registerNavigation();
    }

    private function registerNavigation(): void
    {
        $nav = $this->app->make(\App\Services\Navigation\NavigationRegistry::class);
        $icon = 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0z';

        $items = [
            ['Dashboard', 'commercial.dashboard', ['commercial.view'], 10],
            ['Customers', 'commercial.customers.index', ['customer.view'], 20],
            ['Leads', 'commercial.leads.index', ['lead.view'], 30],
            ['Inquiries', 'commercial.inquiries.index', ['inquiry.view'], 40],
            ['Quotations', 'commercial.quotations.index', ['quotation.view'], 50],
            ['Rate Cards', 'commercial.rate-cards.index', ['pricing.view'], 60],
        ];
        $children = [];
        foreach ($items as [$label, $route, $permission, $order]) {
            $children[] = new \App\Services\Navigation\NavItem(
                module: 'Commercial', label: $label, icon: '',
                route: $route, permission: $permission, group: 'Commercial', order: $order,
            );
        }
        $nav->register(new \App\Services\Navigation\NavItem(
            module: 'Commercial', label: 'Commercial', icon: $icon,
            group: 'Commercial', order: 25, children: $children,
        ));
    }
}
