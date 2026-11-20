<?php

namespace Modules\SystemAdministration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandingAsset extends Model
{
    protected $table = 'system_branding_assets';

    protected $fillable = ['key', 'path', 'original_name', 'mime_type', 'size', 'is_current', 'uploaded_by'];

    protected $casts = ['is_current' => 'boolean'];

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return asset($this->path);
    }
}
