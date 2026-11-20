<?php
namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;

class CommercialCampaign extends Model
{
    protected $fillable = ['reference', 'name', 'type', 'start_date', 'end_date', 'segment_code',
        'discount_type', 'discount_value', 'max_discount', 'usage_limit', 'used_count', 'budget',
        'branch_id', 'status'];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    const TYPES = ['acquisition','discount','seasonal','corporate','referral','agent','service','route'];
    const STATUSES = ['draft','active','paused','ended','cancelled'];

    public function isUsable(): bool
    {
        return $this->status === 'active'
            && now()->between($this->start_date, $this->end_date)
            && ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }
    public function discountFor(float $amount): float
    {
        if (!$this->isUsable() || !$this->discount_type || !$this->discount_value) return 0.0;
        $d = $this->discount_type === 'percent' ? $amount * ($this->discount_value / 100) : (float) $this->discount_value;
        return min($d, $this->max_discount ?? $d);
    }
    public function recordUsage(): void { $this->increment('used_count'); }
}
