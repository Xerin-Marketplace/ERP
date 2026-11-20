<?php
namespace Modules\Delivery\Models;
use Illuminate\Database\Eloquent\Model;

class DeliveryService extends Model
{
    protected $table = 'delivery_services';
    protected $guarded = ['id', 'reference'];
    protected $casts = ['active' => 'boolean', 'requires_signature' => 'boolean', 'requires_otp' => 'boolean', 'requires_photo' => 'boolean'];
}
