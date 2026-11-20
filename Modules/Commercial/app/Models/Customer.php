<?php

namespace Modules\Commercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Extends Finance's `customers` AR table — single customer master. */
class Customer extends Model
{
    use SoftDeletes;
    protected $attributes = ['status' => 'prospect'];
    protected $guarded = ['id', 'code'];
    protected $casts = ['is_active' => 'boolean'];
    protected $appends = ['reference'];

    public const STATUSES = ['prospect','active','inactive','on_hold','restricted','archived'];
    public const TYPES = ['individual','business','agent','wholesale','other'];
    public const SOURCES = ['website','whatsapp','phone','email','walkin','social','referral','agent','other'];

    public function getReferenceAttribute(): string { return $this->code; }
    public function contacts() { return $this->hasMany(CustomerContact::class); }
    public function addresses() { return $this->hasMany(CustomerAddress::class); }
    public function communicationPreference() { return $this->hasOne(\Modules\Portal\Models\CommunicationPreference::class); }
    public function documents() { return $this->hasMany(CustomerDocument::class); }
    public function branch() { return $this->belongsTo(\Modules\Organization\Models\Branch::class); }
    public function owner() { return $this->belongsTo(\App\Models\User::class, 'owner_id'); }
    public function inquiries() { return $this->hasMany(Inquiry::class); }
    public function quotations() { return $this->hasMany(Quotation::class); }
    public function leads() { return $this->hasMany(Lead::class, 'converted_customer_id'); }

    public function scopeFor($q, $user)
    {
        if ($user->can('commercial.all_branches') || ! $user->currentAssignment?->branch_id) return $q;
        return $q->where(fn ($w) => $w->where('branch_id', $user->currentAssignment->branch_id)->orWhereNull('branch_id'));
    }
}
