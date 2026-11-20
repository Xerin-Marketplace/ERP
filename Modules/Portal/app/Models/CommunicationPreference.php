<?php
namespace Modules\Portal\Models;
use Illuminate\Database\Eloquent\Model;

class CommunicationPreference extends Model
{
    protected $table = 'communication_preferences';
    protected $guarded = ['id', 'reference'];
    protected $casts = ['channels' => 'array', 'operational' => 'boolean', 'marketing_consent' => 'boolean'];
    public function customer() { return $this->belongsTo(\Modules\Commercial\Models\Customer::class); }
}
