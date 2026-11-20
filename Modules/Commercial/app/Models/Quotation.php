<?php

namespace Modules\Commercial\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $attributes = ['status' => 'draft'];
    protected $guarded = ['id', 'number'];
    protected $casts = ['breakdown' => 'array', 'pricing_snapshot' => 'array', 'valid_until' => 'date',
        'approved_at' => 'datetime', 'sent_at' => 'datetime', 'accepted_at' => 'datetime'];

    public const STATUSES = ['draft','pending_approval','approved','sent','viewed','negotiation','accepted','rejected','expired','cancelled','superseded'];

    public const TRANSITIONS = [
        'draft' => ['pending_approval','approved','sent','cancelled'],
        'pending_approval' => ['approved','draft','cancelled'],
        'approved' => ['sent','cancelled'],
        'sent' => ['viewed','negotiation','accepted','rejected','expired','superseded'],
        'viewed' => ['negotiation','accepted','rejected','expired','superseded'],
        'negotiation' => ['accepted','rejected','expired','superseded'],
        'accepted' => [], 'rejected' => [], 'expired' => ['superseded'],
        'cancelled' => [], 'superseded' => [],
    ];

    public function isOpen(): bool { return in_array($this->status, ['sent','viewed','negotiation'], true); }
    public function isExpired(): bool { return $this->valid_until?->isPast() && $this->isOpen(); }

    public function customer() { return $this->belongsTo(Customer::class); }
    public function inquiry() { return $this->belongsTo(Inquiry::class); }
    public function lead() { return $this->belongsTo(Lead::class); }
    public function branch() { return $this->belongsTo(\Modules\Organization\Models\Branch::class); }
    public function rateCard() { return $this->belongsTo(RateCard::class); }
    public function items() { return $this->hasMany(QuotationItem::class)->orderBy('sort'); }
    public function events() { return $this->hasMany(QuotationEvent::class)->latest(); }
    public function preparer() { return $this->belongsTo(\App\Models\User::class, 'prepared_by'); }
    public function approver() { return $this->belongsTo(\App\Models\User::class, 'approved_by'); }
    public function parent() { return $this->belongsTo(Quotation::class, 'parent_id'); }
    public function versions() { return $this->hasMany(Quotation::class, 'parent_id'); }
    public function handover() { return $this->hasOne(CommercialHandover::class); }

    public function scopeFor($q, $user)
    {
        if ($user->can('commercial.all_branches') || ! $user->currentAssignment?->branch_id) return $q;
        return $q->where(fn ($w) => $w->where('branch_id', $user->currentAssignment->branch_id)->orWhereNull('branch_id'));
    }
}
