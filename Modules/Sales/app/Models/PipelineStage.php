<?php
namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;

class PipelineStage extends Model
{
    protected $fillable = ['name', 'code', 'position', 'probability', 'active', 'is_closed'];
    protected $casts = ['active' => 'boolean', 'is_closed' => 'boolean'];

    public function scopeActive($q) { return $q->where('active', true)->orderBy('position'); }
}
