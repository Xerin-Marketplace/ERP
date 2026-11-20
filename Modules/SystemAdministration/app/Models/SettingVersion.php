<?php

namespace Modules\SystemAdministration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettingVersion extends Model
{
    protected $table = 'system_setting_versions';

    protected $fillable = ['key', 'group', 'old_value', 'new_value', 'risk', 'changed_by', 'reason'];

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'changed_by');
    }
}
