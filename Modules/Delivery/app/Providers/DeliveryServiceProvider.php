<?php

namespace Modules\Delivery\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use App\Services\Navigation\NavigationRegistry;
use App\Services\Navigation\NavItem;
use Illuminate\Console\Scheduling\Schedule;

class DeliveryServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Delivery';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'delivery';

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
        $icon = 'M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12';
        $children = [];
        foreach ([['Dashboard','delivery.dashboard',['delivery.view'],10],['Orders','delivery.index',['delivery.view'],20],['Drivers','delivery.drivers',['delivery.view'],30],['Vehicles','delivery.vehicles',['delivery.view'],40],['Services & Zones','delivery.services',['delivery.view'],50]] as [$l,$rt,$p,$o]) {
            $children[] = new NavItem(module:'Delivery', label:$l, icon:'', route:$rt, permission:$p, group:'Last-Mile Delivery', order:$o);
        }
        $nav->register(new NavItem(module:'Delivery', label:'Last-Mile Delivery', icon:$icon, group:'Last-Mile Delivery', order:60, children:$children));
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
