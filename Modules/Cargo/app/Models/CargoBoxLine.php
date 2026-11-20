<?php

namespace Modules\Cargo\Models;

use Illuminate\Database\Eloquent\Model;

class CargoBoxLine extends Model
{
    protected $table = 'cargo_box_lines';

    protected $fillable = ['box_id', 'package_id', 'weight', 'added_at'];

    protected $casts = [
        'weight' => 'decimal:2',
        'added_at' => 'datetime',    ];

    public function box()
    {
        return $this->belongsTo(\Modules\Cargo\Models\CargoBox::class, 'box_id');
    }

    public function package()
    {
        return $this->belongsTo(\Modules\Cargo\Models\CargoPackage::class, 'package_id');
    }
}
