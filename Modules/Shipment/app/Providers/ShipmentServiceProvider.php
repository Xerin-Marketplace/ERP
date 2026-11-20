<?php

namespace Modules\Shipment\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use App\Services\Navigation\NavigationRegistry;
use App\Services\Navigation\NavItem;
use Illuminate\Console\Scheduling\Schedule;

class ShipmentServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Shipment';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'shipment';

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
        $icon = 'M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5';
        $children = [];
        foreach ([
            ['Dashboard','shipment.dashboard',['shipment.view'],10],
            ['Shipments','shipment.index',['shipment.view'],20],
            ['Airlines','shipment.airlines',['shipment.view'],30],
            ['Flights','shipment.flights',['shipment.view'],40],
            ['Exceptions','shipment.exceptions',['shipment.view'],50],
        ] as [$l,$rt,$p,$o]) {
            $children[] = new NavItem(module:'Shipment', label:$l, icon:'', route:$rt, permission:$p, group:'Shipment & Airline', order:$o);
        }
        $nav->register(new NavItem(module:'Shipment', label:'Shipment & Airline', icon:$icon, group:'Shipment & Airline', order:40, children:$children));
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
