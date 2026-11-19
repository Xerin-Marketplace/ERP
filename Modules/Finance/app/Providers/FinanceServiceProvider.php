<?php

namespace Modules\Finance\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class FinanceServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Finance';
    protected string $nameLower = 'finance';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        $this->loadViewsFrom(module_path($this->name, 'resources/views'), $this->nameLower);
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerNavigation();
    }

    private function registerNavigation(): void
    {
        $nav = $this->app->make(\App\Services\Navigation\NavigationRegistry::class);
        $icon = 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z';

        $items = [
            ['Finance Dashboard', 'finance.dashboard', ['finance.dashboard.view'], 10],
            ['Chart of Accounts', 'finance.accounts.index', ['finance.accounts.view'], 20],
            ['Journals', 'finance.journals.index', ['finance.journals.view'], 30],
            ['General Ledger', 'finance.ledger.index', ['finance.ledger.view'], 40],
            ['Payables', 'finance.payables.index', ['finance.payables.manage'], 50],
            ['Receivables', 'finance.receivables.index', ['finance.receivables.manage'], 55],
            ['Expenses', 'finance.expenses.index', ['finance.expenses.manage'], 60],
            ['Payments', 'finance.payments.index', ['finance.payments.manage'], 65],
            ['Bank & Cash', 'finance.banks.index', ['finance.banks.manage'], 70],
            ['Budgets', 'finance.budgets.index', ['finance.budgets.manage'], 75],
            ['Fixed Assets', 'finance.assets.index', ['finance.assets.manage'], 80],
            ['Finance Reports', 'finance.reports', ['finance.reports.view'], 90],
            ['Finance Settings', 'finance.settings', ['finance.settings.manage'], 95],
        ];

        foreach ($items as [$label, $route, $permission, $order]) {
            $nav->register(new \App\Services\Navigation\NavItem(
                module: 'Finance', label: $label, icon: $icon,
                route: $route, permission: $permission, group: 'Finance', order: $order,
            ));
        }
    }
}
