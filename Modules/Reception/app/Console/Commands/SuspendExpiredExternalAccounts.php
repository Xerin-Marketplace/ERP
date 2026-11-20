<?php

namespace Modules\Reception\Console\Commands;

use Illuminate\Console\Command;
use Modules\Reception\Services\AccessExpirationService;

class SuspendExpiredExternalAccounts extends Command
{
    protected $signature = 'reception:suspend-expired {--date= : evaluate against this date (YYYY-MM-DD)}';

    protected $description = 'Suspend external user accounts whose access expiry has passed';

    public function handle(AccessExpirationService $svc): int
    {
        $today = $this->option('date') ? \Carbon\Carbon::parse($this->option('date')) : null;
        $n = $svc->suspendExpired($today);
        $this->info("Suspended {$n} expired external account(s).");
        return self::SUCCESS;
    }
}
