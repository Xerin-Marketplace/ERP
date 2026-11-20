<?php

namespace Modules\Commercial\Models;

use Illuminate\Database\Eloquent\Model;

class LeadActivity extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['meta' => 'array'];
    public function lead() { return $this->belongsTo(Lead::class); }
    public function user() { return $this->belongsTo(\App\Models\User::class); }
}
