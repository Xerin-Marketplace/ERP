<?php
namespace Modules\Tender\Models;
use Illuminate\Database\Eloquent\Model;
class TenderTask extends Model
{
    protected $fillable = ['tender_id','title','raci','assignee_id','due_at','status','note'];
    protected $casts = ['due_at'=>'datetime'];
    public function tender() { return $this->belongsTo(Tender::class); }
    public function assignee() { return $this->belongsTo(\App\Models\User::class,'assignee_id'); }
}
