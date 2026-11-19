<?php

namespace Modules\Ticketing\Models;

use Illuminate\Database\Eloquent\Model;

class TicketTaskChecklist extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['done' => 'boolean'];
}
