<?php

namespace Modules\Cargo\Models;

use Illuminate\Database\Eloquent\Model;

class CargoBox extends Model
{
    protected $table = 'cargo_boxes';

    protected $fillable = ['reference', 'branch_id', 'location_id', 'box_type', 'target_weight', 'actual_weight', 'seal_number', 'status', 'trip_id', 'packed_by', 'closed_at', 'notes'];

    protected $casts = [
        'target_weight' => 'decimal:2',
        'actual_weight' => 'decimal:2',
        'closed_at' => 'datetime',    ];

    public function lines()
    {
        return $this->hasMany(\Modules\Cargo\Models\CargoBoxLine::class, 'box_id');
    }

    public function trip()
    {
        return $this->belongsTo(\Modules\Cargo\Models\CargoTrip::class, 'trip_id');
    }

    public function branch()
    {
        return $this->belongsTo(\Modules\Organization\Models\Branch::class, 'branch_id');
    }
}
