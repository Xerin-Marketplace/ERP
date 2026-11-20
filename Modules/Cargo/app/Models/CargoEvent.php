<?php
namespace Modules\Cargo\Models;
use Illuminate\Database\Eloquent\Model;
class CargoEvent extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['meta' => 'array'];
    public function package() { return $this->belongsTo(CargoPackage::class, 'package_id'); }
    public function user() { return $this->belongsTo(\App\Models\User::class); }
}
