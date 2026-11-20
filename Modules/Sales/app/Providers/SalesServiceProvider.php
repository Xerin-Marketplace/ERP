<?php
namespace Modules\Sales\Providers;

use App\Services\Navigation\NavigationRegistry;
use Nwidart\Modules\Support\ModuleServiceProvider;
use App\Services\Navigation\NavItem;
use Modules\Sales\Providers\RouteServiceProvider;

class SalesServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Sales';
    protected string $nameLower = 'sales';
    protected array $providers = [RouteServiceProvider::class];

    public function boot(): void
    {
        parent::boot();
        $nav = $this->app->make(NavigationRegistry::class);
        $items = [
            ['Commercial Dashboard','sales.dashboard',['commercial.dashboard.view'],10],
            ['Pipeline','sales.pipeline',['sales.pipeline.manage'],20],
            ['Targets','sales.targets',['sales.targets.manage'],30],
            ['Contracts','sales.contracts',['contracts.manage'],40],
            ['Agents & Brokers','sales.partners',['partners.manage'],50],
            ['Commissions','sales.commissions',['commissions.manage'],60],
            ['Pricing Simulation','sales.simulate',['pricing.simulate'],70],
            ['Pricing Approvals','sales.approvals',['pricing.approve'],80],
            ['Campaigns','sales.campaigns',['campaigns.manage'],90],
        ];
        $children = array_map(fn($i) => new NavItem(module:'Sales', label:$i[0], icon:'', route:$i[1], permission:$i[2], group:'Commercial', order:$i[3]), $items);
        $nav->register(new NavItem(module:'Sales', label:'Commercial', icon:'', group:'Commercial', order:30, children:$children));
    }
}
