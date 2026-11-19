<?php

namespace Modules\Ticketing\Models;

use Illuminate\Database\Eloquent\Model;

class TicketAutomationRule extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['conditions' => 'array', 'action_params' => 'array', 'is_active' => 'boolean'];
    public function scopeActive($q) { return $q->where('is_active', true); }
}
