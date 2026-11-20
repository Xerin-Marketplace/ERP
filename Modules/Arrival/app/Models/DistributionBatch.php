<?php
namespace Modules\Arrival\Models;
use Illuminate\Database\Eloquent\Model;

class DistributionBatch extends Model
{
    protected $table = 'distribution_batches';
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'open', 'grouping' => 'customer'];
    protected $casts = ['handed_over_at' => 'datetime'];
    public function items() { return $this->hasMany(DistributionBatchItem::class, 'batch_id'); }
    public function packages() { return $this->belongsToMany(\Modules\Cargo\Models\CargoPackage::class, 'distribution_batch_items', 'batch_id', 'package_id')->withTimestamps(); }
    public function customer() { return $this->belongsTo(\Modules\Commercial\Models\Customer::class, 'customer_id'); }
}
