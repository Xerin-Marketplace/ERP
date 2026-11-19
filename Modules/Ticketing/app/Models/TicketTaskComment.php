<?php

namespace Modules\Ticketing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TicketTaskComment extends Model
{
    protected $guarded = ['id'];
    public function author() { return $this->belongsTo(User::class, 'author_id'); }
}
