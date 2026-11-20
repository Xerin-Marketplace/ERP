<?php

namespace Modules\Finance\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Finance\Models\EfdReceipt;

/**
 * EFD transmission job — placeholder boundary. When the TRA gateway
 * adapter is implemented (post tax-adviser confirmation) it plugs in
 * here; until then the job marks receipts failed-with-reason instead
 * of pretending to transmit.
 */
class TransmitEfdReceipt implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(public EfdReceipt $receipt) {}

    public function handle(): void
    {
        $receipt = $this->receipt->fresh();
        if (! $receipt || $receipt->status !== 'queued') return;

        $receipt->increment('attempts');
        $receipt->update(['status' => 'failed', 'response' => [
            'error' => 'gateway_not_configured',
            'message' => 'EFD transmission deferred — TRA gateway settings not configured.',
        ]]);
    }
}
