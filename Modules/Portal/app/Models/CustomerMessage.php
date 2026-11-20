<?php
namespace Modules\Portal\Models;
use Illuminate\Database\Eloquent\Model;

class CustomerMessage extends Model
{
    protected $table = 'customer_messages';
    protected $guarded = ['id', 'reference'];
    public function customer() { return $this->belongsTo(\Modules\Commercial\Models\Customer::class); }
}
