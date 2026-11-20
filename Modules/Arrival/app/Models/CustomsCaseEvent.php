<?php
namespace Modules\Arrival\Models;
use Illuminate\Database\Eloquent\Model;

class CustomsCaseEvent extends Model
{
    protected $table = 'customs_case_events';
    protected $guarded = ['id', 'reference'];
    protected $casts = ['meta' => 'array'];
    public function case() { return $this->belongsTo(CustomsCase::class, 'case_id'); }
}
