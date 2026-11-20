<?php

namespace Modules\Commercial\Models;

use Illuminate\Database\Eloquent\Model;

class CommercialHandover extends Model
{
    protected $guarded = ['id', 'reference'];
    protected $casts = ['snapshot' => 'array'];
    public function quotation() { return $this->belongsTo(Quotation::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function inquiry() { return $this->belongsTo(Inquiry::class); }
    public function branch() { return $this->belongsTo(\Modules\Organization\Models\Branch::class); }
}
