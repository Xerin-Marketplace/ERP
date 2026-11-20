<?php
namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOpportunity extends Model
{
    protected $fillable = ['reference', 'lead_id', 'customer_id', 'stage_id', 'name', 'service_category',
        'expected_volume', 'expected_revenue', 'expected_margin', 'probability', 'expected_close',
        'owner_id', 'branch_id', 'quotation_id', 'contract_id', 'status', 'lost_reason', 'competitor', 'next_action'];
    protected $casts = ['expected_close' => 'date'];

    const STATUSES = ['open', 'won', 'lost', 'on_hold'];

    public function lead()      { return $this->belongsTo(\Modules\Commercial\Models\Lead::class); }
    public function customer()  { return $this->belongsTo(\Modules\Commercial\Models\Customer::class); }
    public function stage()     { return $this->belongsTo(PipelineStage::class); }
    public function owner()     { return $this->belongsTo(\App\Models\User::class, 'owner_id'); }
    public function quotation() { return $this->belongsTo(\Modules\Commercial\Models\Quotation::class); }
    public function contract()  { return $this->belongsTo(CommercialContract::class); }
    public function scopeForBranch($q, $branchId) { return $branchId ? $q->where('branch_id', $branchId) : $q; }
}
