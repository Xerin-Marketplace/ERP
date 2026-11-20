<?php
namespace Modules\Delivery\Models;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $table = 'vehicles';
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'active'];
    protected $casts = ['insurance_expiry' => 'date', 'inspection_expiry' => 'date'];
    public function isAvailable(): bool
    { return $this->status === 'active'
        && (! $this->insurance_expiry || $this->insurance_expiry->isFuture())
        && (! $this->inspection_expiry || $this->inspection_expiry->isFuture()); }
}
