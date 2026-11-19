<?php

namespace Modules\Ticketing\Models;

use Illuminate\Database\Eloquent\Model;

class TicketTag extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    public function ticket() { return $this->belongsTo(Ticket::class); }
}
