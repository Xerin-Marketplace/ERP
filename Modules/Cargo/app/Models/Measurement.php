<?php
namespace Modules\Cargo\Models;
use Illuminate\Database\Eloquent\Model;
class Measurement extends Model
{
    protected $table = 'cargo_measurements';
    protected $guarded = ['id'];
    public function package() { return $this->belongsTo(CargoPackage::class, 'package_id'); }
    public function measurer() { return $this->belongsTo(\App\Models\User::class, 'measured_by'); }
}
