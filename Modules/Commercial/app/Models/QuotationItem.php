<?php

namespace Modules\Commercial\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    protected $guarded = ['id'];
    public function quotation() { return $this->belongsTo(Quotation::class); }
}
