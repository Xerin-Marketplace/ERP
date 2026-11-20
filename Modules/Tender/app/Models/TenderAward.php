<?php
namespace Modules\Tender\Models;
use Illuminate\Database\Eloquent\Model;
class TenderAward extends Model
{
    protected $fillable = ['tender_id','award_ref','awarded_at','value','currency',
        'scope','conditions','contract_sign_deadline','notice_document_id','recorded_by'];
    protected $casts = ['awarded_at'=>'date','contract_sign_deadline'=>'date','value'=>'decimal:2'];
    public function tender() { return $this->belongsTo(Tender::class); }
}
