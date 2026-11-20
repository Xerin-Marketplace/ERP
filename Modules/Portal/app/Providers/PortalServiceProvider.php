<?php

namespace Modules\Portal\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use App\Services\Navigation\NavigationRegistry;
use App\Services\Navigation\NavItem;
use Illuminate\Console\Scheduling\Schedule;

class PortalServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Portal';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'portal';

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
        RouteServiceProvider::class,
    ];


    public function boot(): void
    {
        parent::boot();
        $nav = $this->app->make(NavigationRegistry::class);
        $children = [];
        foreach ([['My Portal','portal.dashboard',['portal.access'],10],['Service Desk','desk.dashboard',['portal.staff'],20]] as [$l,$rt,$p,$o]) {
            $children[] = new NavItem(module:'Portal', label:$l, icon:'', route:$rt, permission:$p, group:'Customer Experience', order:$o);
        }
        $nav->register(new NavItem(module:'Portal', label:'Customer Experience', icon:'', group:'Customer Experience', order:70, children:$children));
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
