<?php

namespace Modules\Ticketing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketApproval extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['decided_at' => 'datetime'];

    public function ticket(): BelongsTo { return $this->belongsTo(Ticket::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approver_id'); }
    public function decider(): BelongsTo { return $this->belongsTo(User::class, 'decided_by'); }
    public function scopePending($q) { return $q->where('status', 'pending'); }
}
