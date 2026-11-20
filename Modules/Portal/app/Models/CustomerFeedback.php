<?php
namespace Modules\Portal\Models;
use Illuminate\Database\Eloquent\Model;

class CustomerFeedback extends Model
{
    protected $table = 'customer_feedback';
    protected $guarded = ['id', 'reference'];
    public function customer() { return $this->belongsTo(\Modules\Commercial\Models\Customer::class); }
}
