<?php
namespace Modules\Shipment\Models;
use Illuminate\Database\Eloquent\Model;

class ShipmentDocument extends Model
{
    protected $table = 'shipment_documents';
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['review_status' => 'pending', 'version' => 1];
    protected $casts = ['issue_date' => 'date', 'expiry_date' => 'date'];
    public function shipment() { return $this->belongsTo(Shipment::class); }
}
