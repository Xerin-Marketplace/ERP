<?php
namespace Modules\Tender\Models;
use Illuminate\Database\Eloquent\Model;
class TenderDocumentVersion extends Model
{
    protected $fillable = ['document_id','version','path','original_name','size','change_note','uploaded_by'];
    public function document() { return $this->belongsTo(TenderDocument::class,'document_id'); }
}
