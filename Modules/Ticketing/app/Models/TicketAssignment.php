<?php

namespace Modules\Ticketing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAssignment extends Model
{
    public const UPDATED_AT = null;
    protected $guarded = ['id'];
    protected $casts = ['created_at' => 'datetime'];

    public function ticket(): BelongsTo { return $this->belongsTo(Ticket::class); }
    public function agent(): BelongsTo { return $this->belongsTo(User::class, 'agent_id'); }
    public function team(): BelongsTo { return $this->belongsTo(TicketTeam::class, 'team_id'); }
    public function assigner(): BelongsTo { return $this->belongsTo(User::class, 'assigned_by'); }
}
