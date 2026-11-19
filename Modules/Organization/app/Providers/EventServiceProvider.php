<?php

namespace Modules\Organization\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \Modules\Organization\Events\CompanyCreated::class => [\Modules\Organization\Listeners\InvalidateOrganizationCache::class],
        \Modules\Organization\Events\CompanyUpdated::class => [\Modules\Organization\Listeners\InvalidateOrganizationCache::class],
        \Modules\Organization\Events\CompanyStatusChanged::class => [\Modules\Organization\Listeners\InvalidateOrganizationCache::class],
        \Modules\Organization\Events\BranchCreated::class => [\Modules\Organization\Listeners\InvalidateOrganizationCache::class],
        \Modules\Organization\Events\BranchUpdated::class => [\Modules\Organization\Listeners\InvalidateOrganizationCache::class],
        \Modules\Organization\Events\BranchStatusChanged::class => [\Modules\Organization\Listeners\InvalidateOrganizationCache::class],
        \Modules\Organization\Events\LocationCreated::class => [\Modules\Organization\Listeners\InvalidateOrganizationCache::class],
        \Modules\Organization\Events\LocationUpdated::class => [\Modules\Organization\Listeners\InvalidateOrganizationCache::class],
        \Modules\Organization\Events\LocationStatusChanged::class => [\Modules\Organization\Listeners\InvalidateOrganizationCache::class],
        \Modules\Organization\Events\DepartmentCreated::class => [\Modules\Organization\Listeners\InvalidateOrganizationCache::class],
        \Modules\Organization\Events\DepartmentUpdated::class => [\Modules\Organization\Listeners\InvalidateOrganizationCache::class],
        \Modules\Organization\Events\DepartmentStatusChanged::class => [\Modules\Organization\Listeners\InvalidateOrganizationCache::class],
        \Modules\Organization\Events\DepartmentTransferred::class => [\Modules\Organization\Listeners\InvalidateOrganizationCache::class],
        \Modules\Organization\Events\OrganizationalStructureChanged::class => [\Modules\Organization\Listeners\InvalidateOrganizationCache::class],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = false;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
