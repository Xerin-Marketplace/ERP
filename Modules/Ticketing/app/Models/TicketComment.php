<?php

namespace Modules\Ticketing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketComment extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['is_internal' => 'boolean'];

    public function ticket(): BelongsTo { return $this->belongsTo(Ticket::class); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }
    public function attachments(): HasMany { return $this->hasMany(TicketAttachment::class, 'comment_id'); }
}
