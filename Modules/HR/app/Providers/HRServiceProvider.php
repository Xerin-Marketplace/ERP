<?php

namespace Modules\HR\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Nwidart\Modules\Support\ModuleServiceProvider;

class HRServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'HR';
    protected string $nameLower = 'hr';

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
        $icon = 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z';

        $items = [
            ['HR Dashboard', 'hr.dashboard', ['hr.dashboard.view'], 10],
            ['My HR', 'hr.me', null, 15],
            ['Attendance', 'hr.attendance.index', ['attendance.view'], 20],
            ['Leave', 'hr.leave.index', ['leave.view', 'leave.request'], 30],
            ['Leave Approvals', 'hr.leave.approvals', ['leave.approve'], 35],
            ['Shifts', 'hr.shifts.index', ['shift.view', 'shift.manage'], 40],
            ['Overtime', 'hr.overtime.index', ['overtime.request', 'overtime.manage'], 45],
            ['Recruitment', 'hr.recruitment.index', ['recruitment.view', 'recruitment.manage'], 50],
            ['Performance', 'hr.performance.index', ['performance.view'], 55],
            ['Training', 'hr.training.index', ['training.view'], 60],
            ['Disciplinary', 'hr.disciplinary.index', ['disciplinary.view'], 65],
            ['Welfare', 'hr.welfare.index', ['welfare.view'], 70],
            ['Compensation', 'hr.compensation.index', ['hr.compensation.view'], 75],
            ['Payroll', 'hr.payroll.index', ['hr.payroll.view'], 80],
            ['HR Reports', 'hr.reports', ['attendance.reports.view', 'hr.dashboard.view'], 90],
        ];

        foreach ($items as [$label, $route, $permission, $order]) {
            $nav->register(new \App\Services\Navigation\NavItem(
                module: 'HR', label: $label, icon: $icon,
                route: $route, permission: $permission, group: 'Human Resources', order: $order,
            ));
        }
    }
}
