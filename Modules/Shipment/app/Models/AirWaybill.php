<?php
namespace Modules\Shipment\Models;
use Illuminate\Database\Eloquent\Model;

class AirWaybill extends Model
{
    protected $table = 'air_waybills';
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'draft', 'kind' => 'hawb', 'version' => 1];
    protected $casts = ['issued_at' => 'datetime'];
    public function shipment() { return $this->belongsTo(Shipment::class); }
    public function parent() { return $this->belongsTo(AirWaybill::class, 'parent_id'); }
    public function children() { return $this->hasMany(AirWaybill::class, 'parent_id'); }
}
