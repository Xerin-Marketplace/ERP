<?php
namespace Modules\WorkCenter\Providers;
use Illuminate\Support\ServiceProvider;
class WorkCenterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'workcenter');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
