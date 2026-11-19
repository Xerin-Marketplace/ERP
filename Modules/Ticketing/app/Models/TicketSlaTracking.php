<?php

namespace Modules\Ticketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketSlaTracking extends Model
{
    protected $table = 'ticket_sla_tracking';
    protected $guarded = ['id'];
    protected $casts = [
        'response_due_at' => 'datetime', 'response_at' => 'datetime', 'resolution_due_at' => 'datetime',
        'paused_at' => 'datetime', 'response_breached' => 'boolean', 'resolution_breached' => 'boolean',
        'response_warned' => 'boolean', 'resolution_warned' => 'boolean', 'paused_minutes' => 'integer',
    ];

    public function ticket(): BelongsTo { return $this->belongsTo(Ticket::class); }
    public function policy(): BelongsTo { return $this->belongsTo(TicketSlaPolicy::class, 'policy_id'); }
}
