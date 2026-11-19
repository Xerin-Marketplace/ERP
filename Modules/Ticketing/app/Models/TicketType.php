<?php

namespace Modules\Ticketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Organization\Models\Department;

class TicketType extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['approval_chain' => 'array', 'is_active' => 'boolean'];

    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function scopeActive($q) { return $q->where('is_active', true); }
}
