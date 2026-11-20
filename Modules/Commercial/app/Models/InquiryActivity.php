<?php

namespace Modules\Commercial\Models;

use Illuminate\Database\Eloquent\Model;

class InquiryActivity extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['meta' => 'array'];
    public function inquiry() { return $this->belongsTo(Inquiry::class); }
    public function user() { return $this->belongsTo(\App\Models\User::class); }
}
