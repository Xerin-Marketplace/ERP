<?php

namespace Modules\Organization\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchSetting extends Model
{
    protected $fillable = ['branch_id', 'key', 'value', 'type', 'updated_by'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Typed accessor consistent with system_settings casting. */
    public function typedValue(): mixed
    {
        return match ($this->type) {
            'bool', 'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'int', 'integer' => (int) $this->value,
            'json', 'array' => json_decode($this->value ?? 'null', true),
            default => $this->value,
        };
    }
}
