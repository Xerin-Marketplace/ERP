<?php

namespace Modules\Cargo\Services;

use Modules\Cargo\Models\CargoPackage;
use Modules\Cargo\Models\CargoTrip;
use Modules\Delivery\Models\DeliveryOrderPackage;
use Modules\Finance\Models\CustomerInvoice;

/**
 * Profitability (spec §15.8):
 *   package profit = invoiced revenue − allocated trip cost − delivery cost
 *   trip profit    = revenue of all packages on the leg − trip cost
 * Revenue counts posted/paid invoices only; costs are allocated by weight share.
 */
class ShipmentProfitService
{
    /** Posted+paid revenue invoiced against a package. */
    public function packageRevenue(CargoPackage $p): float
    {
        return (float) CustomerInvoice::where('package_id', $p->id)
            ->whereIn('status', ['posted', 'part_paid', 'paid'])
            ->sum('total');
    }

    /** Package's weight-share of its trip's passenger fee. */
    public function tripCostShare(CargoPackage $p): float
    {
        $line = $p->boxLine ?? \Modules\Cargo\Models\CargoBoxLine::where('package_id', $p->id)
            ->whereHas('box', fn ($q) => $q->whereNotNull('trip_id'))->first();
        if (! $line) return 0.0;
        $trip = $line->box->trip;
        if (! $trip || ! $trip->passenger_fee) return 0.0;
        $total = (float) $trip->boxes()->sum('actual_weight');
        if ($total <= 0) return 0.0;
        $lineWeight = (float) ($line->weight ?: $p->gross_weight ?: 0);
        return round((float) $trip->passenger_fee * ($lineWeight / $total), 2);
    }

    /** Delivery fees from orders containing this package. */
    public function deliveryCost(CargoPackage $p): float
    {
        return (float) DeliveryOrderPackage::where('package_id', $p->id)
            ->whereNull('removed_at')
            ->join('delivery_orders', 'delivery_order_packages.order_id', '=', 'delivery_orders.id')
            ->whereIn('delivery_orders.status', ['released', 'out_for_delivery', 'delivered', 'closed'])
            ->sum('delivery_orders.fee');
    }

    public function packageProfit(CargoPackage $p): array
    {
        $revenue = $this->packageRevenue($p);
        $tripCost = $this->tripCostShare($p);
        $delivery = $this->deliveryCost($p);
        $costs = $tripCost + $delivery;
        $profit = round($revenue - $costs, 2);
        return [
            'revenue' => $revenue, 'trip_cost' => $tripCost, 'delivery_cost' => $delivery,
            'total_cost' => $costs, 'profit' => $profit,
            'margin_pct' => $revenue > 0 ? round($profit / $revenue * 100, 1) : null,
        ];
    }

    /** Trip profit = revenue of all packaged on the leg − leg cost (§15.8). */
    public function tripProfit(CargoTrip $trip): array
    {
        $packages = CargoPackage::whereIn('id', function ($q) use ($trip) {
            $q->select('package_id')->from('cargo_box_lines')
                ->join('cargo_boxes', 'cargo_box_lines.box_id', '=', 'cargo_boxes.id')
                ->where('cargo_boxes.trip_id', $trip->id);
        })->get();

        $revenue = $packages->sum(fn ($p) => $this->packageRevenue($p));
        $legCost = (float) ($trip->passenger_fee ?? 0);
        $delivery = $packages->sum(fn ($p) => $this->deliveryCost($p));
        $costs = $legCost + $delivery;
        return [
            'packages' => $packages->count(), 'revenue' => $revenue,
            'leg_cost' => $legCost, 'delivery_cost' => $delivery,
            'total_cost' => $costs, 'profit' => round($revenue - $costs, 2),
            'margin_pct' => $revenue > 0 ? round(($revenue - $costs) / $revenue * 100, 1) : null,
        ];
    }

    /** Branch/route rollup for management dashboards. */
    public function summary(?int $branchId = null): array
    {
        $packages = CargoPackage::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get();
        $revenue = $costs = 0;
        foreach ($packages as $p) {
            $revenue += $this->packageRevenue($p);
            $costs += $this->tripCostShare($p) + $this->deliveryCost($p);
        }
        return ['packages' => $packages->count(), 'revenue' => round($revenue, 2),
            'costs' => round($costs, 2), 'profit' => round($revenue - $costs, 2),
            'margin_pct' => $revenue > 0 ? round(($revenue - $costs) / $revenue * 100, 1) : null];
    }
}
