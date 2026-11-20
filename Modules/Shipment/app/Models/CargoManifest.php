<?php
namespace Modules\Shipment\Models;
use Illuminate\Database\Eloquent\Model;

class CargoManifest extends Model
{
    protected $table = 'cargo_manifests';
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'draft', 'version' => 1];
    protected $casts = ['reconciliation' => 'array', 'accepted_at' => 'datetime'];
    public function shipment() { return $this->belongsTo(Shipment::class); }
    public function flight() { return $this->belongsTo(Flight::class); }
}
