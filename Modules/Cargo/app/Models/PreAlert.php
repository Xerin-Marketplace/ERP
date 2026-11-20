<?php
namespace Modules\Cargo\Models;
use Illuminate\Database\Eloquent\Model;

class PreAlert extends Model
{
    protected $table = 'cargo_pre_alerts';
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'draft'];
    protected $casts = ['expected_date' => 'date'];
    public const STATUSES = ['draft','submitted','approved','awaiting_delivery','scheduled','partially_received','fully_received','cancelled','expired','on_hold'];
    public function customer() { return $this->belongsTo(\Modules\Commercial\Models\Customer::class, 'customer_id'); }
    public function handover() { return $this->belongsTo(\Modules\Commercial\Models\CommercialHandover::class, 'handover_id'); }
    public function branch() { return $this->belongsTo(\Modules\Organization\Models\Branch::class); }
    public function receivings() { return $this->hasMany(Receiving::class, 'pre_alert_id'); }
    public function scopeFor($q, $u) { return $u->can('cargo.all_branches') || ! $u->currentAssignment?->branch_id ? $q : $q->where('branch_id', $u->currentAssignment->branch_id); }
}
