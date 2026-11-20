<?php

namespace Modules\Reception\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalUserProfile extends Model
{
    protected $table = 'external_user_profiles';

    protected $fillable = ['user_id', 'access_request_id', 'category', 'organization', 'sponsor_id', 'expires_on', 'sponsor_review_at'];

    protected $casts = [
        'expires_on' => 'date',
        'sponsor_review_at' => 'datetime',    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function sponsor()
    {
        return $this->belongsTo(\App\Models\User::class, 'sponsor_id');
    }

    public function accessRequest()
    {
        return $this->belongsTo(\Modules\Reception\Models\AccessRequest::class, 'access_request_id');
    }
}
