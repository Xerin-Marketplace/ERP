<?php

namespace Modules\Ticketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceFormField extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['options' => 'array', 'required' => 'boolean'];

    public function service(): BelongsTo { return $this->belongsTo(ServiceCatalogItem::class, 'service_id'); }
}
