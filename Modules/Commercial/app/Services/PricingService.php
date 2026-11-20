<?php

namespace Modules\Commercial\Services;

use Modules\Commercial\Models\RateCard;

/** Explainable cargo pricing: chargeable weight + rate-card breakdown. */
class PricingService
{
    public const DEFAULT_DIVISOR = 6000; // cm³ per kg (IATA air-cargo standard)

    /** Volumetric weight from cm dimensions. */
    public function volumetricWeight(float $l, float $w, float $h, int $pieces = 1, ?int $divisor = null): float
    {
        $d = $divisor ?: self::DEFAULT_DIVISOR;
        return round(($l * $w * $h * max(1, $pieces)) / $d, 2);
    }

    public function chargeableWeight(?float $gross, ?float $volumetric): float
    {
        return round(max($gross ?? 0, $volumetric ?? 0), 2);
    }

    /** Build itemized breakdown from a rate card. */
    public function quote(RateCard $card, float $chargeableWeight): array
    {
        $lines = [];
        if ($card->base_rate > 0) $lines[] = ['label' => 'Base freight', 'kind' => 'charge', 'amount' => (float) $card->base_rate];
        if ($card->per_kg_rate > 0 && $chargeableWeight > 0) {
            $lines[] = ['label' => "Weight charge ({$chargeableWeight} kg × {$card->per_kg_rate})", 'kind' => 'charge', 'amount' => round($card->per_kg_rate * $chargeableWeight, 2)];
        }
        $subtotal = array_sum(array_column(array_filter($lines, fn ($l) => $l['kind'] === 'charge'), 'amount'));
        if ($card->minimum_charge > 0 && $subtotal < $card->minimum_charge) {
            $lines[] = ['label' => 'Minimum charge adjustment', 'kind' => 'charge', 'amount' => round($card->minimum_charge - $subtotal, 2)];
        }
        $base = array_sum(array_column($lines, 'amount'));
        $surchargeTotal = 0;
        foreach ($card->surcharges ?? [] as $s) {
            $amt = ($s['type'] ?? 'flat') === 'percent' ? round($base * ($s['amount'] / 100), 2) : (float) $s['amount'];
            $lines[] = ['label' => $s['label'] ?? ucfirst($s['key'] ?? 'Surcharge'), 'kind' => 'surcharge', 'amount' => $amt];
            $surchargeTotal += $amt;
        }
        return [
            'lines' => $lines,
            'subtotal' => round($base, 2),
            'surcharge_total' => round($surchargeTotal, 2),
            'total' => round($base + $surchargeTotal, 2),
            'currency' => $card->currency,
        ];
    }
}
