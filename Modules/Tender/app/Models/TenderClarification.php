<?php
namespace Modules\Tender\Models;
use Illuminate\Database\Eloquent\Model;
class TenderClarification extends Model
{
    protected $fillable = ['tender_id','direction','subject','content','response',
        'received_at','responded_at','status','handled_by'];
    protected $casts = ['received_at'=>'date','responded_at'=>'date'];
    public function tender() { return $this->belongsTo(Tender::class); }
}
