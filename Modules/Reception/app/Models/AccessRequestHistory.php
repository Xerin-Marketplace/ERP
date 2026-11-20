<?php

namespace Modules\Reception\Models;

use Illuminate\Database\Eloquent\Model;

class AccessRequestHistory extends Model
{
    protected $table = 'access_request_history';

    protected $fillable = ['request_id', 'actor_id', 'event', 'from_status', 'to_status', 'payload', 'internal', 'ip'];

    protected $casts = [
        'payload' => 'array',
        'internal' => 'boolean',    ];

    public function request()
    {
        return $this->belongsTo(\Modules\Reception\Models\AccessRequest::class, 'request_id');
    }

    public function actor()
    {
        return $this->belongsTo(\App\Models\User::class, 'actor_id');
    }
}
