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

class AttendanceDevice extends Model
{
    protected $table = 'attendance_devices';
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
}
