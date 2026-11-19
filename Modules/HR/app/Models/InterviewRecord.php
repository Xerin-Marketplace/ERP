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

class InterviewRecord extends Model
{
    protected $table = 'interview_records';
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }

    public function application(): BelongsTo { return $this->belongsTo(RecruitmentApplication::class); }
    public function interviewer(): BelongsTo { return $this->belongsTo(User::class); }
}
