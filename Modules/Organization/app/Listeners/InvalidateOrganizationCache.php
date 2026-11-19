<?php

namespace Modules\Organization\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Modules\Organization\Events\OrganizationalEvent;

/**
 * Queued listener — clears cached organization statistics whenever an
 * organizational entity changes so dashboards never serve stale counts.
 */
class InvalidateOrganizationCache implements ShouldQueue
{
    public function handle(OrganizationalEvent $event): void
    {
        Cache::forget('organization.overview');
        Cache::forget('organization.structure');
    }
}
