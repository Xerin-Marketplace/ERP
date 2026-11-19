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

class RecruitmentApplication extends Model
{
    protected $table = 'recruitment_applications';
    protected $guarded = ['id', 'reference'];
    public function vacancy(): BelongsTo { return $this->belongsTo(JobVacancy::class); }
    public function cv(): BelongsTo { return $this->belongsTo(ManagedFile::class, 'cv_file_id'); }
    public function interviews(): HasMany { return $this->hasMany(InterviewRecord::class, 'application_id'); }
}
