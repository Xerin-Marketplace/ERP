<?php

namespace Modules\SystemAdministration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureFlag extends Model
{
    protected $table = 'system_feature_flags';

    protected $fillable = ['key', 'label', 'description', 'is_enabled', 'scope', 'starts_at', 'ends_at', 'updated_by'];

    protected $casts = [
        'is_enabled' => 'boolean',
        'scope' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}
