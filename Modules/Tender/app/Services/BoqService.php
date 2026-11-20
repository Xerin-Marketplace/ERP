<?php

namespace Modules\Tender\Services;

use Modules\Tender\Models\TenderBoq;
use Modules\Tender\Models\TenderBoqItem;

/**
 * BOQ engine — decimal-safe (bcmath) financial math.
 * Original issuer quantity/description is preserved immutably;
 * internal costing adjustments live on the costing columns only.
 */
class BoqService
{
    /** Cost components summed into unit rate when unit_rate not overridden. */
    private const COMPONENTS = ['material_cost','labour_cost','equipment_cost',
        'transport_cost','other_cost','subcontract_cost'];

    public function addItem(TenderBoq $boq, array $data): TenderBoqItem
    {
        $data['boq_id'] = $boq->id;
        $data['original_description'] ??= $data['description'];
        $data['original_quantity'] ??= $data['quantity'];
        return TenderBoqItem::create($data);
    }

    public function unitRate(TenderBoqItem $i): string
    {
        if ($i->unit_rate !== null) return (string) $i->unit_rate;
        $sum = '0';
        foreach (self::COMPONENTS as $c) $sum = bcadd($sum, (string) ($i->$c ?? 0), 4);
        return $sum;
    }

    public function lineTotal(TenderBoqItem $i): string
    {
        return bcmul((string) $i->quantity, $this->unitRate($i), 4);
    }

    /** Full totals incl. overhead/profit/tax/contingency percentages. */
    public function totals(TenderBoq $boq): array
    {
        $direct = '0';
        foreach ($boq->items as $i) $direct = bcadd($direct, $this->lineTotal($i), 4);
        $pct = fn (string $base, $p) => bcdiv(bcmul($base, (string) ($p ?? 0), 4), '100', 4);
        $overhead = $pct($direct, $boq->overhead_pct);
        $cost = bcadd($direct, $overhead, 4);
        $contingency = $pct($cost, $boq->contingency_pct);
        $profit = $pct(bcadd($cost, $contingency, 4), $boq->profit_pct);
        $subtotal = bcadd(bcadd($cost, $contingency, 4), $profit, 4);
        $tax = $pct($subtotal, $boq->tax_pct);
        $grand = bcadd($subtotal, $tax, 4);
        return [
            'direct' => $direct, 'overhead' => $overhead, 'cost' => $cost,
            'contingency' => $contingency, 'profit' => $profit,
            'subtotal' => $subtotal, 'tax' => $tax, 'grand_total' => $grand,
            'margin_pct' => bccomp($grand, '0', 4) > 0
                ? bcmul(bcdiv($profit, $grand, 6), '100', 2) : '0',
        ];
    }
}
