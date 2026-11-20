<?php
namespace Modules\Arrival\Models;
use Illuminate\Database\Eloquent\Model;

class DistributionBatchItem extends Model
{
    protected $table = 'distribution_batch_items';
    protected $guarded = ['id', 'reference'];
    public function batch() { return $this->belongsTo(DistributionBatch::class, 'batch_id'); }
    public function package() { return $this->belongsTo(\Modules\Cargo\Models\CargoPackage::class, 'package_id'); }
}
