<?php

namespace Modules\Employee\Models;

use App\Models\ManagedFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDocument extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array { return ['expires_at' => 'date']; }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function category(): BelongsTo { return $this->belongsTo(DocumentCategory::class, 'category_id'); }
    public function file(): BelongsTo { return $this->belongsTo(ManagedFile::class, 'managed_file_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function isExpired(): bool { return $this->expires_at && $this->expires_at->isPast(); }
    public function expiringSoon(int $days = 30): bool
    {
        return $this->expires_at && $this->expires_at->isFuture() && $this->expires_at->lte(now()->addDays($days));
    }
}
