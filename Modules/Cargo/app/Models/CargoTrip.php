<?php

namespace Modules\Cargo\Models;

use Illuminate\Database\Eloquent\Model;

class CargoTrip extends Model
{
    protected $table = 'cargo_trips';

    protected $fillable = ['reference', 'origin_branch_id', 'destination_branch_id', 'passenger_name', 'passenger_id_doc', 'passenger_phone', 'airline', 'flight_number', 'flight_date', 'baggage_allowance', 'passenger_fee', 'currency', 'status', 'handover_dxb_by', 'handover_dxb_at', 'handover_tz_by', 'handover_tz_at', 'departed_at', 'arrived_at', 'departure_evidence_file_id', 'notes'];

    protected $casts = [
        'flight_date' => 'date',
        'baggage_allowance' => 'decimal:2',
        'passenger_fee' => 'decimal:2',
        'handover_dxb_at' => 'datetime',
        'handover_tz_at' => 'datetime',
        'departed_at' => 'datetime',
        'arrived_at' => 'datetime',    ];

    public function boxes()
    {
        return $this->hasMany(\Modules\Cargo\Models\CargoBox::class, 'trip_id');
    }

    public function originBranch()
    {
        return $this->belongsTo(\Modules\Organization\Models\Branch::class, 'origin_branch_id');
    }

    public function destinationBranch()
    {
        return $this->belongsTo(\Modules\Organization\Models\Branch::class, 'destination_branch_id');
    }
}
