<?php
namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionRule extends Model
{
    const BASES = ['revenue_pct', 'per_shipment', 'per_acquisition', 'tiered'];
    protected $fillable = ['partner_id', 'beneficiary_type', 'beneficiary_id', 'basis', 'rate',
        'fixed_amount', 'currency', 'effective_from', 'effective_to', 'requires_collected', 'status'];
    protected $casts = ['effective_from' => 'date', 'effective_to' => 'date', 'requires_collected' => 'boolean'];

    public function partner() { return $this->belongsTo(CommercialPartner::class); }
    public function isEffective(): bool
    {
        if (($this->status ?? 'active') !== 'active') return false;
        $today = now()->toDateString();
        if ($this->effective_from && $this->effective_from->toDateString() > $today) return false;
        if ($this->effective_to && $this->effective_to->toDateString() < $today) return false;
        return true;
    }
}
