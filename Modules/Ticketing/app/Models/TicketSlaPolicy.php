<?php

namespace Modules\Ticketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketSlaPolicy extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'business_days' => 'array', 'pauses_on_pending' => 'boolean', 'is_active' => 'boolean',
    ];

    public function type(): BelongsTo { return $this->belongsTo(TicketType::class, 'ticket_type_id'); }
    public function scopeActive($q) { return $q->where('is_active', true); }
}
