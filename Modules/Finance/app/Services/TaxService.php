<?php

namespace Modules\Finance\Services;

use Illuminate\Validation\ValidationException;
use Modules\Finance\Models\TaxRate;
use Modules\Finance\Models\TaxTransaction;

class TaxService
{
    /** Compute tax preserving the applied rate snapshot — reproducible. */
    public function compute(TaxRate $rate, float $base): float
    {
        $r = (float) $rate->rate / 100;
        $tax = $rate->inclusive ? $base * $r / (1 + $r) : $base * $r;
        return round($tax, 2);
    }

    public function record(int $companyId, TaxRate $rate, string $documentType, int $documentId, float $base): TaxTransaction
    {
        return TaxTransaction::create([
            'company_id'   => $companyId,
            'tax_rate_id'  => $rate->id,
            'document_type' => $documentType,
            'document_id'  => $documentId,
            'base_amount'  => $base,
            'tax_amount'   => $this->compute($rate, $base),
            'rate_applied' => $rate->rate,
        ]);
    }

    public function activeRateFor(int $categoryId, string $date): ?TaxRate
    {
        return TaxRate::where('category_id', $categoryId)->where('is_active', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date))
            ->orderByDesc('effective_from')->first();
    }
}
