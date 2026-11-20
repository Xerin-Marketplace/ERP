<?php

namespace Modules\Arrival\Models;

use Illuminate\Database\Eloquent\Model;

class StorageRateSetting extends Model
{
    protected $table = 'storage_rate_settings';

    protected $fillable = ['branch_id', 'free_days', 'rate_per_kg_day', 'currency', 'effective_from', 'effective_to'];

    protected $casts = [
        'free_days' => 'integer',
        'rate_per_kg_day' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',    ];

    public function branch()
    {
        return $this->belongsTo(\Modules\Organization\Models\Branch::class, 'branch_id');
    }
}
