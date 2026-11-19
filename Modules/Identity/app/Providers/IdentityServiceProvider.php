<?php

namespace Modules\Identity\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class IdentityServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Identity';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'identity';

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
        \Illuminate\Support\Facades\Event::subscribe(\Modules\Identity\Listeners\RecordLoginHistory::class);
        // Navigation: identity entries (Users, Roles, Registrations) are
        // registered as children of the core System menu in AppServiceProvider.
    }
}
