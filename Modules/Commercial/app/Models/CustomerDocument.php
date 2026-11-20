<?php

namespace Modules\Commercial\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerDocument extends Model
{
    protected $guarded = ['id'];

    public function customer() { return $this->belongsTo(Customer::class); }
}
