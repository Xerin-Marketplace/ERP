<?php

namespace Modules\Ticketing\Models;

use Illuminate\Database\Eloquent\Model;

class TicketEscalationRule extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['is_active' => 'boolean'];
    public function scopeActive($q) { return $q->where('is_active', true); }
}
