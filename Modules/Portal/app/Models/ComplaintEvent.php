<?php
namespace Modules\Portal\Models;
use Illuminate\Database\Eloquent\Model;

class ComplaintEvent extends Model
{
    protected $table = 'complaint_events';
    protected $guarded = ['id', 'reference'];
    public function complaint() { return $this->belongsTo(Complaint::class); }
}
