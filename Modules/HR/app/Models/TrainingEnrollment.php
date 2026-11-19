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

class TrainingEnrollment extends Model
{
    protected $table = 'training_enrollments';
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function program(): BelongsTo { return $this->belongsTo(TrainingProgram::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
