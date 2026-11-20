<?php

namespace Modules\Arrival\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use App\Services\Navigation\NavigationRegistry;
use App\Services\Navigation\NavItem;
use Illuminate\Console\Scheduling\Schedule;

class ArrivalServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Arrival';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'arrival';

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
        $icon = 'M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21';
        $children = [];
        foreach ([['Dashboard','arrival.dashboard',['arrival.view'],10],['Arrivals','arrival.index',['arrival.view'],20],['Agents','arrival.agents',['customs.view'],30],['Distribution','arrival.batches',['distribution.manage'],40]] as [$l,$rt,$p,$o]) {
            $children[] = new NavItem(module:'Arrival', label:$l, icon:'', route:$rt, permission:$p, group:'TZ Arrival & Customs', order:$o);
        }
        $nav->register(new NavItem(module:'Arrival', label:'TZ Arrival & Customs', icon:$icon, group:'TZ Arrival & Customs', order:50, children:$children));
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
