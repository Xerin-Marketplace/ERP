<?php

namespace Modules\Ticketing\Console;

use Illuminate\Console\Command;
use Modules\Ticketing\Services\SlaService;

class TicketSlaScan extends Command
{
    protected $signature = 'tickets:sla-scan';

    protected $description = 'Scan tickets for SLA warnings, breaches, escalations and auto-close.';

    public function handle(SlaService $sla): int
    {
        $stats = $sla->scan();
        $this->info(json_encode($stats));

        return self::SUCCESS;
    }
}
