<?php
namespace Modules\Cargo\Models;
use Illuminate\Database\Eloquent\Model;
class Warehouse extends Model
{
    protected $guarded = ['id'];
    public function branch() { return $this->belongsTo(\Modules\Organization\Models\Branch::class); }
    public function locations() { return $this->hasMany(WarehouseLocation::class); }
}
