<?php

namespace Modules\Commercial\Services;

use Illuminate\Support\Facades\DB;
use Modules\Commercial\Models\Customer;
use Modules\Commercial\Models\Lead;
use Modules\Commercial\Models\LeadActivity;

class LeadService
{
    public function __construct(private SequenceService $seq, private CustomerService $customers) {}

    public function create(array $data, int $userId): Lead
    {
        return DB::transaction(function () use ($data, $userId) {
            $lead = new Lead($data + ['owner_id' => $data['owner_id'] ?? $userId]);
            $lead->forceFill(['reference' => $this->seq->lead()])->save();
            $this->log($lead, 'note', 'Lead created', $userId);
            return $lead;
        });
    }

    public function transition(Lead $lead, string $stage, int $userId, ?string $reason = null): Lead
    {
        abort_unless(in_array($stage, Lead::STAGES, true), 422, 'Invalid stage');
        $from = $lead->stage;
        $lead->update(['stage' => $stage] + ($stage === 'lost' ? ['lost_reason' => $reason] : []));
        $this->log($lead, 'stage_change', "{$from} → {$stage}" . ($reason ? " — {$reason}" : ''), $userId);
        return $lead;
    }

    /** Convert lead → customer, reusing an existing customer when phone/email matches. */
    public function convert(Lead $lead, int $userId, ?int $existingCustomerId = null): Customer
    {
        return DB::transaction(function () use ($lead, $userId, $existingCustomerId) {
            $customer = $existingCustomerId
                ? Customer::findOrFail($existingCustomerId)
                : ($this->customers->findDuplicates($lead->phone, $lead->email)->first()
                    ?? $this->customers->create([
                        'type' => $lead->company ? 'business' : 'individual',
                        'name' => $lead->company ?: $lead->name,
                        'trading_name' => $lead->company ? $lead->name : null,
                        'email' => $lead->email, 'phone' => $lead->phone,
                        'contact_person' => $lead->contact_person ?: $lead->name,
                        'source' => $lead->source, 'branch_id' => $lead->branch_id,
                        'owner_id' => $lead->owner_id, 'status' => 'prospect',
                    ], $userId));
            $lead->update(['converted_customer_id' => $customer->id, 'converted_at' => now(), 'stage' => 'won']);
            $this->log($lead, 'converted', "Converted to customer {$customer->code}", $userId);
            return $customer;
        });
    }

    public function log(Lead $lead, string $type, string $body, ?int $userId, array $meta = []): void
    {
        LeadActivity::create(['lead_id' => $lead->id, 'user_id' => $userId, 'type' => $type, 'body' => $body, 'meta' => $meta ?: null]);
    }
}
