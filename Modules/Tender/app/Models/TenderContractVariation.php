<?php
namespace Modules\Tender\Models;
use Illuminate\Database\Eloquent\Model;
class TenderContractVariation extends Model
{
    protected $fillable = ['contract_id','ref','type','description','value_delta','days_delta','status','approved_by'];
    protected $casts = ['value_delta'=>'decimal:2'];
    public function contract() { return $this->belongsTo(TenderContract::class,'contract_id'); }
}
