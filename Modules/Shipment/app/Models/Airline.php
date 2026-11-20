<?php
namespace Modules\Shipment\Models;
use Illuminate\Database\Eloquent\Model;

class Airline extends Model
{
    protected $table = 'airlines';
    protected $guarded = ['id', 'reference'];
    protected $casts = ['routes' => 'array'];
}
