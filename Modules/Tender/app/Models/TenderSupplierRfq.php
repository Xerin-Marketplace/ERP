<?php
namespace Modules\Tender\Models;
use Illuminate\Database\Eloquent\Model;
class TenderSupplierRfq extends Model
{
    protected $fillable = ['tender_id','ref','issued_at','due_at','status','scope'];
    protected $casts = ['issued_at'=>'date','due_at'=>'date'];
    public function tender() { return $this->belongsTo(Tender::class); }
    public function quotations() { return $this->hasMany(TenderQuotation::class,'rfq_id'); }
}
