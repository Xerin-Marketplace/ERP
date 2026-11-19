<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['user_id', 'disk', 'path', 'original_name', 'mime_type', 'size', 'visibility', 'metadata'])]
class ManagedFile extends Model
{
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'size' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function url(): ?string
    {
        return $this->visibility === 'public'
            ? Storage::disk($this->disk)->url($this->path)
            : null;
    }
}
