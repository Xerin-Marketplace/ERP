<?php

namespace Modules\Commercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;
    protected $attributes = ['stage' => 'new', 'priority' => 'normal'];
    protected $guarded = ['id', 'reference'];
    protected $casts = ['follow_up_at' => 'datetime', 'converted_at' => 'datetime', 'estimated_value' => 'decimal:2'];

    public const STAGES = ['new','contacted','qualified','needs_assessment','quote_requested','quote_sent','negotiation','won','lost','disqualified'];
    public const SOURCES = ['website','whatsapp','phone','email','walkin','social','referral','existing_customer','agent','other'];

    public function activities() { return $this->hasMany(LeadActivity::class)->latest(); }
    public function owner() { return $this->belongsTo(\App\Models\User::class, 'owner_id'); }
    public function branch() { return $this->belongsTo(\Modules\Organization\Models\Branch::class); }
    public function customer() { return $this->belongsTo(Customer::class, 'converted_customer_id'); }

    public function scopeFor($q, $user)
    {
        if ($user->can('commercial.all_branches') || ! $user->currentAssignment?->branch_id) return $q;
        return $q->where(fn ($w) => $w->where('branch_id', $user->currentAssignment->branch_id)->orWhereNull('branch_id'));
    }
}
