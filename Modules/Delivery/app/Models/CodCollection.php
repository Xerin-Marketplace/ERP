<?php
namespace Modules\Delivery\Models;
use Illuminate\Database\Eloquent\Model;

class CodCollection extends Model
{
    protected $table = 'cod_collections';
    protected $guarded = ['id', 'reference'];
    protected $casts = ['reconciled_at' => 'datetime'];
    public function order() { return $this->belongsTo(DeliveryOrder::class, 'order_id'); }
}
