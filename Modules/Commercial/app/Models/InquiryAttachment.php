<?php

namespace Modules\Commercial\Models;

use Illuminate\Database\Eloquent\Model;

class InquiryAttachment extends Model
{
    protected $guarded = ['id'];
    public function inquiry() { return $this->belongsTo(Inquiry::class); }
}
