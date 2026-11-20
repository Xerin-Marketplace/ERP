<?php

namespace Modules\Cargo\Services;

use App\Services\AuditService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Modules\Arrival\Services\StorageService;
use Modules\Cargo\Models\CargoPackage;
use Modules\Commercial\Models\RateCard;
use Modules\Delivery\Models\DeliveryZone;
use Modules\Finance\Models\ChartOfAccount;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Services\ReceivableService;

/**
 * Quote-to-cash for a package (spec §13, §14.1): freight from the rate card
 * (chargeable = max(actual, volumetric)), storage from the effective-dated
 * rule, delivery from the zone table. Nothing is hard-coded.
 */
class PackageBillingService
{
    /** invoice component => revenue account code */
    public const ACCOUNTS = [
        'freight' => ['4110', 'Freight Revenue'],
        'storage' => ['4120', 'Storage Revenue'],
        'delivery' => ['4130', 'Delivery & Collection Revenue'],
        'handling' => ['4140', 'Handling & Packing Revenue'],
        'customs_fee' => ['4150', 'Customs Service Fee'],
        'pickup' => ['4160', 'Pickup Revenue'],
    ];

    public function __construct(
        private StorageService $storage,
        private ReceivableService $receivables,
        private AuditService $audit,
    ) {}

    /** Price breakdown without persisting anything. */
    public function quote(CargoPackage $package, array $opts = []): array
    {
        $kg = $this->chargeableWeight($package);
        $lines = [];

        // Freight — rate card match; no card = no charge (management must supply tariff).
        $card = RateCard::match([
            'service_type' => $opts['service_type'] ?? $package->booking?->service ?? 'air_cargo',
            'branch_id' => $package->branch_id,
            'origin_country' => 'United Arab Emirates',
            'destination_country' => 'Tanzania',
        ]);
        $freight = $card
            ? max((float) $card->minimum_charge, (float) $card->base_rate + $kg * (float) $card->per_kg_rate)
            : 0;
        $lines['freight'] = [
            'amount' => round($freight, 2), 'kg' => $kg,
            'rate_card' => $card?->code, 'per_kg' => $card ? (float) $card->per_kg_rate : null,
            'currency' => $card->currency ?? 'TZS',
        ];

        // Storage — effective-dated rule from the DC receive date.
        $receivedAt = $opts['received_at'] ?? $package->receiving?->received_at ?? $package->created_at;
        $lines['storage'] = $this->storage->charge($package, Carbon::parse($receivedAt));

        // Delivery / collection option — zone fee table.
        $zone = isset($opts['zone_id']) ? DeliveryZone::find($opts['zone_id']) : null;
        $lines['delivery'] = [
            'amount' => $zone ? (float) $zone->fee : 0,
            'zone' => $zone?->name,
        ];

        $lines['handling'] = ['amount' => (float) ($opts['handling_fee'] ?? 0)];
        $lines['pickup'] = ['amount' => (float) ($opts['pickup_fee'] ?? 0)];
        $lines['total'] = round(collect($lines)->sum('amount'), 2);
        return $lines;
    }

    /** Create a draft customer invoice with one line per charge component. */
    public function invoice(CargoPackage $package, array $opts, int $userId): CustomerInvoice
    {
        abort_if(! $package->customer_id, 422, 'Package has no customer — match a booking first.');
        $lines = $this->quote($package, $opts);
        abort_if($lines['total'] <= 0, 422, 'Nothing to invoice — no rate card, no storage, no fees.');

        $companyId = $package->customer->company_id;
        $invoiceLines = [];
        foreach (self::ACCOUNTS as $key => [$code, $name]) {
            $amount = $lines[$key]['amount'] ?? 0;
            if ($amount <= 0) continue;
            $invoiceLines[] = [
                'description' => $this->describe($key, $package, $lines[$key]),
                'account_id' => $this->revenueAccount($companyId, $code, $name)->id,
                'quantity' => 1, 'unit_price' => $amount,
            ];
        }
        abort_if(! $invoiceLines, 422, 'No chargeable lines.');

        $invoice = $this->receivables->createInvoice([
            'company_id' => $companyId, 'customer_id' => $package->customer_id,
            'invoice_date' => now()->toDateString(),
            'currency' => $opts['currency'] ?? $lines['freight']['currency'] ?? 'TZS',
            'lines' => $invoiceLines,
        ], $userId);

        $this->audit->log('cargo.package_invoiced', $package,
            new: ['invoice' => $invoice->reference, 'total' => $invoice->total]);
        return $invoice;
    }

    private function describe(string $key, CargoPackage $package, array $line): string
    {
        $trk = $package->tracking_no ?? $package->reference;
        return match ($key) {
            'freight' => "Freight {$trk} — {$line['kg']} KG @ {$line['per_kg']} (card {$line['rate_card']})",
            'storage' => "Storage {$trk} — {$line['chargeable_days']} chargeable days",
            'delivery' => "Delivery {$trk}" . ($line['zone'] ? " — {$line['zone']}" : ''),
            default => ucfirst(str_replace('_', ' ', $key))." {$trk}",
        };
    }

    /** Greater of actual vs volumetric weight (spec §14.1), 0.1 KG precision. */
    public function chargeableWeight(CargoPackage $package): float
    {
        $actual = (float) ($package->chargeable_weight ?: $package->gross_weight ?: 0);
        $vol = (float) ($package->volumetric_weight ?: 0);
        return round(max($actual, $vol), 1);
    }

    private function revenueAccount(int $companyId, string $code, string $name): ChartOfAccount
    {
        return ChartOfAccount::firstOrCreate(
            ['company_id' => $companyId, 'code' => $code],
            ['name' => $name, 'type' => 'revenue', 'normal_balance' => 'credit',
             'postable' => true, 'is_active' => true]
        );
    }
}
