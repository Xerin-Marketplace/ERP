<?php

namespace Modules\Organization\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchAdministrator extends Model
{
    protected $fillable = ['branch_id', 'user_id', 'assigned_by', 'delegable_roles', 'delegable_permissions', 'status', 'expires_at', 'suspended_at', 'suspended_by', 'revoked_at', 'revoked_by'];

    protected function casts(): array
    {
        return [
            'delegable_roles' => 'array',
            'delegable_permissions' => 'array',
            'expires_at' => 'datetime',
            'suspended_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }
}
