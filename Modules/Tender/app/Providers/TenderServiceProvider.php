<?php

namespace Modules\Tender\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class TenderServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Tender';
    protected string $nameLower = 'tender';

    public function boot(): void
    {
        $this->loadViewsFrom(module_path($this->name, 'resources/views'), $this->nameLower);
        $this->loadRoutesFrom(module_path($this->name, 'routes/web.php'));
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }
}
