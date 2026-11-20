<?php

namespace Modules\Commercial\Models;

use Illuminate\Database\Eloquent\Model;

class RateCard extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['surcharges' => 'array', 'effective_from' => 'date', 'effective_to' => 'date', 'approved_at' => 'datetime'];

    public const STATUSES = ['draft','pending_approval','approved','active','expired','archived'];

    public function branch() { return $this->belongsTo(\Modules\Organization\Models\Branch::class); }
    public function approver() { return $this->belongsTo(\App\Models\User::class, 'approved_by'); }

    /** Best matching active card for a route/service, lowest priority number wins. */
    public static function match(array $c): ?self
    {
        return static::where('status', 'active')
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', now()->toDateString()))
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', now()->toDateString()))
            ->when($c['origin_country'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('origin_country', $v)->orWhereNull('origin_country')))
            ->when($c['destination_country'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('destination_country', $v)->orWhereNull('destination_country')))
            ->when($c['service_type'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('service_type', $v)->orWhereNull('service_type')))
            ->when($c['branch_id'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('branch_id', $v)->orWhereNull('branch_id')))
            ->orderBy('priority')->first();
    }
}
