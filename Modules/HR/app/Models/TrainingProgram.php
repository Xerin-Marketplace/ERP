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

class TrainingProgram extends Model
{
    protected $table = 'training_programs';
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date', 'certifies' => 'boolean', 'budget decimal:2'];
    }

    public function enrollments(): HasMany { return $this->hasMany(TrainingEnrollment::class, 'program_id'); }
}
