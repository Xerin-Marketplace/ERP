<?php

namespace Modules\SystemAdministration\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class SystemAdministrationServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'SystemAdministration';

    protected string $nameLower = 'systemadministration';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        $this->loadViewsFrom(module_path($this->name, 'resources/views'), $this->nameLower);
        if ($this->app->runningInConsole()) {
            $this->commands([\Modules\SystemAdministration\Console\Commands\XerinCommand::class]);
        }
        // Navigation: control-center entries are children of the core
        // System menu registered in AppServiceProvider.
    }
}
