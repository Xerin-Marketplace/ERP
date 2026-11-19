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

class ShiftDefinition extends Model
{
    protected $table = 'shift_definitions';
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['working_days' => 'array', 'overnight' => 'boolean', 'is_active' => 'boolean', 'start_time' => 'datetime:H:i', 'end_time' => 'datetime:H:i'];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function assignments(): HasMany { return $this->hasMany(ShiftAssignment::class, 'shift_id'); }
}
