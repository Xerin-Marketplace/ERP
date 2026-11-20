<?php
namespace Modules\Portal\Models;
use Illuminate\Database\Eloquent\Model;

class PrivacyRequest extends Model
{
    protected $table = 'privacy_requests';
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'submitted'];
    public function customer() { return $this->belongsTo(\Modules\Commercial\Models\Customer::class); }
}
