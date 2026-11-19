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

class JobVacancy extends Model
{
    protected $table = 'job_vacancies';
    protected $guarded = ['id', 'reference'];
    protected function casts(): array
    {
        return ['published_at' => 'date', 'closes_at' => 'date'];
    }

    public function requisition(): BelongsTo { return $this->belongsTo(RecruitmentRequisition::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function applications(): HasMany { return $this->hasMany(RecruitmentApplication::class, 'vacancy_id'); }
}
