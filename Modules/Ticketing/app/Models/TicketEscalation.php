<?php

namespace Modules\Ticketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketEscalation extends Model
{
    public const UPDATED_AT = null;
    protected $guarded = ['id'];
    protected $casts = ['created_at' => 'datetime', 'details' => 'array'];

    public function ticket(): BelongsTo { return $this->belongsTo(Ticket::class); }
    public function rule(): BelongsTo { return $this->belongsTo(TicketEscalationRule::class, 'rule_id'); }
}
