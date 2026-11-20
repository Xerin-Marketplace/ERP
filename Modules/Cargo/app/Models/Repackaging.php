<?php
namespace Modules\Cargo\Models;
use Illuminate\Database\Eloquent\Model;
class Repackaging extends Model
{
    protected $table = 'cargo_repackagings';
    protected $guarded = ['id'];
    protected $casts = ['materials' => 'array', 'before_snapshot' => 'array'];
    public function package() { return $this->belongsTo(CargoPackage::class, 'package_id'); }
}
