<?php
namespace Modules\Cargo\Models;
use Illuminate\Database\Eloquent\Model;

class Receiving extends Model
{
    protected $table = 'cargo_receivings';
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'in_progress', 'condition' => 'good', 'unidentified' => false];
    protected $casts = ['received_at' => 'datetime', 'unidentified' => 'boolean'];
    public const OUTCOMES = ['fully','partially','excess','short','damaged','wrong_customer','unidentified','rejected'];
    public function preAlert() { return $this->belongsTo(PreAlert::class, 'pre_alert_id'); }
    public function customer() { return $this->belongsTo(\Modules\Commercial\Models\Customer::class, 'customer_id'); }
    public function branch() { return $this->belongsTo(\Modules\Organization\Models\Branch::class); }
    public function receiver() { return $this->belongsTo(\App\Models\User::class, 'received_by'); }
    public function packages() { return $this->hasMany(CargoPackage::class, 'receiving_id'); }
    public function scopeFor($q, $u) { return $u->can('cargo.all_branches') || ! $u->currentAssignment?->branch_id ? $q : $q->where('branch_id', $u->currentAssignment->branch_id); }
}
