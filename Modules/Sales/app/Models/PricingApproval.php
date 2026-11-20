<?php
namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class PricingApproval extends Model
{
    protected $fillable = ['reference', 'entity_type', 'entity_ref', 'original_value',
        'requested_value', 'reason', 'requested_by', 'decided_by', 'decided_at', 'status'];
    protected $casts = ['decided_at' => 'datetime'];

    const TYPES = ['discount','special_rate','below_minimum','contract_exception','rate_override','credit_terms','low_margin'];
    const TRANSITIONS = ['pending' => ['approved', 'rejected'], 'approved' => [], 'rejected' => []];

    public function requester() { return $this->belongsTo(\App\Models\User::class, 'requested_by'); }
    public function decider()   { return $this->belongsTo(\App\Models\User::class, 'decided_by'); }

    public function decide(string $decision, int $userId): void
    {
        if ($this->status !== 'pending') throw new InvalidArgumentException("Approval {$this->reference} already decided");
        if (!in_array($decision, ['approved', 'rejected'], true)) throw new InvalidArgumentException('Invalid decision');
        $this->update(['status' => $decision, 'decided_by' => $userId, 'decided_at' => now()]);
    }
}
