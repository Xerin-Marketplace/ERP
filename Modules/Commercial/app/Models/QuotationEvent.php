<?php

namespace Modules\Commercial\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationEvent extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['meta' => 'array'];
    public function quotation() { return $this->belongsTo(Quotation::class); }
    public function user() { return $this->belongsTo(\App\Models\User::class); }
}
