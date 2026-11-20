<?php
namespace Modules\Arrival\Models;
use Illuminate\Database\Eloquent\Model;

class ReleaseOverride extends Model
{
    protected $table = 'release_overrides';
    protected $guarded = ['id', 'reference'];
    public function package() { return $this->belongsTo(\Modules\Cargo\Models\CargoPackage::class, 'package_id'); }
    public function user() { return $this->belongsTo(\App\Models\User::class); }
}
