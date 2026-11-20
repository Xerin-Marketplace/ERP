<?php
namespace Modules\Cargo\Models;
use Illuminate\Database\Eloquent\Model;
class CargoIncident extends Model
{
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'open', 'severity' => 'medium'];
    protected $casts = ['resolved_at' => 'datetime'];
    public const CATEGORIES = ['short_received','excess','wrong_customer','unidentified','damaged','missing_label','dup_reference','incorrect_weight','packaging_failure','restricted','missing_docs','location_conflict','missing_package','unauthorized_movement'];
    public function package() { return $this->belongsTo(CargoPackage::class, 'package_id'); }
    public function branch() { return $this->belongsTo(\Modules\Organization\Models\Branch::class); }
    public function assignee() { return $this->belongsTo(\App\Models\User::class, 'assigned_to'); }
    public function scopeFor($q, $u) { return $u->can('cargo.all_branches') || ! $u->currentAssignment?->branch_id ? $q : $q->where('branch_id', $u->currentAssignment->branch_id); }
}
