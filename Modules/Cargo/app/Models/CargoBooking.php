<?php

namespace Modules\Cargo\Models;

use Illuminate\Database\Eloquent\Model;

class CargoBooking extends Model
{
    protected $table = 'cargo_bookings';

    protected $fillable = ['reference', 'customer_id', 'branch_id', 'quotation_id', 'service', 'carriage_mode', 'origin', 'destination', 'collection_type', 'supplier_name', 'supplier_order_ref', 'goods_description', 'expected_packages', 'declared_weight', 'status', 'created_by'];

    protected $casts = [
    ];

    public function customer()
    {
        return $this->belongsTo(\Modules\Commercial\Models\Customer::class, 'customer_id');
    }

    public function branch()
    {
        return $this->belongsTo(\Modules\Organization\Models\Branch::class, 'branch_id');
    }

    public function packages()
    {
        return $this->hasMany(\Modules\Cargo\Models\CargoPackage::class, 'booking_id');
    }
}
