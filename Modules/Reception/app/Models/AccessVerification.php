<?php

namespace Modules\Reception\Models;

use Illuminate\Database\Eloquent\Model;

class AccessVerification extends Model
{
    protected $table = 'access_verifications';

    protected $fillable = ['request_id', 'type', 'status', 'reviewer_id', 'evidence', 'notes', 'verified_at'];

    protected $casts = [
        'verified_at' => 'datetime',    ];

    public function request()
    {
        return $this->belongsTo(\Modules\Reception\Models\AccessRequest::class, 'request_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewer_id');
    }
}
