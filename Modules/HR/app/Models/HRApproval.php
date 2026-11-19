<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Employee\Models\Employee;
use Modules\Organization\Models\{Branch, Company, Department};
use App\Models\User;
use App\Models\ManagedFile;

class HRApproval extends Model
{
    protected $table = 'hr_approvals';
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function approvable(): MorphTo { return $this->morphTo(); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class); }
    public function scopePending($q) { return $q->where('status', 'pending'); }
}
