<?php
namespace Modules\Cargo\Models;
use Illuminate\Database\Eloquent\Model;
class WarehouseLocation extends Model
{
    protected $guarded = ['id'];
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function packages() { return $this->hasMany(CargoPackage::class, 'location_id'); }
    public function occupied(): int { return $this->packages()->count(); }
    public function occupancyPct(): ?int { return $this->capacity ? min(100, (int) round($this->occupied() / $this->capacity * 100)) : null; }
}
