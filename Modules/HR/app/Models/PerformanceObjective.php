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

class PerformanceObjective extends Model
{
    protected $table = 'performance_objectives';
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['weight decimal:2'];
    }

    public function cycle(): BelongsTo { return $this->belongsTo(PerformanceCycle::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
