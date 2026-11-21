<?php

namespace Modules\Commercial\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Commercial\Models\CommercialHandover;
use Modules\Commercial\Models\Quotation;
use Modules\Commercial\Models\QuotationEvent;
use Modules\Commercial\Models\QuotationItem;
use Modules\Commercial\Models\RateCard;

class QuotationService
{
    public function __construct(private SequenceService $seq, private PricingService $pricing) {}

    /** Create a quotation; when a rate card matches, auto-compute the breakdown. */
    public function create(array $data, int $userId): Quotation
    {
        return DB::transaction(function () use ($data, $userId) {
            $quote = new Quotation($data + [
                'prepared_by' => $userId,
                'acceptance_token' => Str::random(48),
            ]);
            $quote->forceFill(['number' => $this->seq->quotation()]);

            $card = ! empty($data['rate_card_id'])
                ? RateCard::findOrFail($data['rate_card_id'])
                : RateCard::match([
                    'origin_country' => $data['origin_country'] ?? null,
                    'destination_country' => $data['destination_country'] ?? null,
                    'service_type' => $data['service_type'] ?? null,
                    'branch_id' => $data['branch_id'] ?? null,
                ]);

            if ($card && ($data['auto_price'] ?? true)) {
                $cw = $this->pricing->chargeableWeight($quote->gross_weight, $quote->volumetric_weight);
                $b = $this->pricing->quote($card, $cw);
                $quote->forceFill([
                    'rate_card_id' => $card->id, 'chargeable_weight' => $cw,
                    'volumetric_divisor' => $card->volumetric_divisor,
                    'currency' => $b['currency'], 'subtotal' => $b['subtotal'],
                    'surcharge_total' => $b['surcharge_total'], 'total' => $b['total'] - ($data['discount'] ?? 0),
                    'discount' => $data['discount'] ?? 0, 'breakdown' => $b['lines'],
                    'pricing_snapshot' => ['rate_card' => $card->only(['id','code','name','version','currency','per_kg_rate','base_rate','minimum_charge','volumetric_divisor']), 'computed_at' => now()->toIso8601String()],
                ]);
            }
            $quote->save();
            foreach (($quote->breakdown ?? []) as $i => $line) {
                QuotationItem::create(['quotation_id' => $quote->id, 'sort' => $i] + $line);
            }
            $this->log($quote, 'created', $userId);
            return $quote;
        });
    }

    /** New version of a quotation — supersedes the old, preserves its snapshot. */
    public function revise(Quotation $quote, array $data, int $userId, ?string $reason = null): Quotation
    {
        return DB::transaction(function () use ($quote, $data, $userId, $reason) {
            abort_if($quote->status === 'accepted', 422, 'Accepted quotations cannot be revised — create a new quotation.');
            $new = $quote->replicate(['number', 'version', 'accepted_at', 'acceptance_channel', 'sent_at', 'approved_at', 'approved_by']);
            $new->forceFill([
                'number' => $this->seq->quotation(),
                'version' => $quote->version + 1,
                'parent_id' => $quote->id,
                'status' => 'draft',
                'prepared_by' => $userId,
                'acceptance_token' => Str::random(48),
            ])->fill($data);
            $new->save();
            foreach ($data['items'] ?? [] as $i => $line) {
                QuotationItem::create(['quotation_id' => $new->id, 'sort' => $i] + $line);
            }
            if ($quote->isOpen()) $quote->update(['status' => 'superseded']);
            $this->log($new, 'revised', $userId, ['from' => $quote->number, 'reason' => $reason]);
            return $new;
        });
    }

    public function transition(Quotation $q, string $to, int $userId, array $meta = []): Quotation
    {
        abort_unless(in_array($to, Quotation::TRANSITIONS[$q->status] ?? [], true), 422, "Cannot move quotation from {$q->status} to {$to}");
        // Spec: no self-approval — the preparer cannot approve own quotation.
        abort_if($to === 'approved' && (int) $q->prepared_by === (int) $userId,
            403, 'Preparer cannot approve their own quotation.');
        $q->update(['status' => $to] + match ($to) {
            'approved' => ['approved_by' => $userId, 'approved_at' => now()],
            'sent' => ['sent_at' => now()],
            'accepted' => ['accepted_at' => now(), 'acceptance_channel' => $meta['channel'] ?? 'manual'],
            default => [],
        });
        $this->log($q, $to === 'pending_approval' ? 'submitted' : $to, $userId, $meta);
        return $q;
    }

    /** Customer acceptance via signed link or staff-recorded. Exactly one handover. */
    public function accept(Quotation $q, string $channel, ?int $userId = null): Quotation
    {
        abort_if($q->status === 'superseded' || $q->parent?->versions()->where('status', 'accepted')->exists(), 422, 'Superseded quotation cannot be accepted.');
        abort_if($q->isExpired(), 422, 'Quotation has expired.');
        abort_unless($q->isOpen(), 422, "Quotation is {$q->status} and cannot be accepted.");

        return DB::transaction(function () use ($q, $channel, $userId) {
            $this->transition($q, 'accepted', $userId ?? 0, ['channel' => $channel]);
            $h = CommercialHandover::firstOrNew(['quotation_id' => $q->id]);
            $h->forceFill([
                'reference' => $h->reference ?? $this->seq->handover(),
                'customer_id' => $q->customer_id, 'inquiry_id' => $q->inquiry_id,
                'branch_id' => $q->branch_id, 'handed_over_by' => $userId,
                'snapshot' => [
                    'quotation' => $q->only(['number','version','origin','destination','cargo_description','package_count','chargeable_weight','currency','total']),
                    'pricing' => $q->pricing_snapshot, 'payment_terms' => $q->payment_terms,
                    'customer' => $q->customer?->only(['code','name','email','phone']),
                ],
            ])->save();
            $this->log($q, 'handover', $userId, ['channel' => $channel]);
            return $q->fresh();
        });
    }

    public function log(Quotation $q, string $event, ?int $userId, array $meta = []): void
    {
        QuotationEvent::create(['quotation_id' => $q->id, 'user_id' => $userId ?: null, 'event' => $event, 'meta' => $meta ?: null]);
    }
}
