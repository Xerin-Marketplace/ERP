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

class ShiftAssignment extends Model
{
    protected $table = 'shift_assignments';
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['effective_from' => 'date', 'effective_to' => 'date'];
    }

    public function shift(): BelongsTo { return $this->belongsTo(ShiftDefinition::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function assigner(): BelongsTo { return $this->belongsTo(User::class, 'assigned_by'); }
}
