<?php

namespace Modules\Ticketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketCategory extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['is_active' => 'boolean'];

    public function parent(): BelongsTo { return $this->belongsTo(TicketCategory::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(TicketCategory::class, 'parent_id'); }
    public function defaultTeam(): BelongsTo { return $this->belongsTo(TicketTeam::class, 'default_team_id'); }
    public function scopeActive($q) { return $q->where('is_active', true); }
}
