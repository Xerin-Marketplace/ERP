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

class AttendanceImportLog extends Model
{
    protected $table = 'attendance_import_logs';
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['error_detail' => 'array'];
    }

    public function device(): BelongsTo { return $this->belongsTo(AttendanceDevice::class); }
    public function importer(): BelongsTo { return $this->belongsTo(User::class, 'imported_by'); }
}
