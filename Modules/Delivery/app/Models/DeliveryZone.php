<?php
namespace Modules\Delivery\Models;
use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    protected $table = 'delivery_zones';
    protected $guarded = ['id', 'reference'];
    protected $casts = ['active' => 'boolean', 'remote' => 'boolean'];
}
