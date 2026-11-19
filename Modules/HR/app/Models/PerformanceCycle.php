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

class PerformanceCycle extends Model
{
    protected $table = 'performance_cycles';
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date'];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function objectives(): HasMany { return $this->hasMany(PerformanceObjective::class, 'cycle_id'); }
    public function reviews(): HasMany { return $this->hasMany(PerformanceReview::class, 'cycle_id'); }
}
