<?php

namespace Modules\Tender\Services;

use Modules\Tender\Models\{Tender, TenderSecurity};

/**
 * Tender security validity — PPRA guidance: tender security must remain
 * valid for the tender validity period PLUS 28 days. Flag insufficient cover.
 */
class SecurityValidityService
{
    public const EXTENSION_DAYS = 28;

    /** Required expiry = submission deadline + tender validity days + 28. */
    public function requiredExpiry(Tender $t): ?\Carbon\Carbon
    {
        if (! $t->submission_deadline || ! $t->validity_days) return $t->securities()->value('required_until') ? \Carbon\Carbon::parse($t->securities()->value('required_until')) : null;
        return $t->submission_deadline->copy()->addDays((int) $t->validity_days + self::EXTENSION_DAYS);
    }

    /** @return array{ok:bool, required:?string, actual:?string, shortfall_days:int} */
    public function check(TenderSecurity $sec): array
    {
        $required = $sec->required_until;
        $ok = ! $required || $sec->expires_at->gte($required);
        $shortfall = (! $ok && $required)
            ? (int) \Carbon\Carbon::parse($sec->expires_at)->diffInDays($required) : 0;
        return ['ok' => $ok, 'required' => (string) $required,
            'actual' => (string) $sec->expires_at, 'shortfall_days' => $ok ? 0 : $shortfall];
    }
}
