<?php
namespace Modules\Arrival\Models;
use Illuminate\Database\Eloquent\Model;

class ImportDocument extends Model
{
    protected $table = 'import_documents';
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['review_status' => 'pending', 'version' => 1];
    protected $casts = ['issue_date' => 'date', 'expiry_date' => 'date'];
    public function arrival() { return $this->belongsTo(ShipmentArrival::class, 'arrival_id'); }
}
