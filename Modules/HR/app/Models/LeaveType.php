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

class LeaveType extends Model
{
    protected $table = 'leave_types';
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['paid' => 'boolean', 'requires_document' => 'boolean', 'is_active' => 'boolean', 'eligibility' => 'array', 'approval_chain' => 'array'];
    }

    public function balances(): HasMany { return $this->hasMany(LeaveBalance::class); }
    public function requests(): HasMany { return $this->hasMany(LeaveRequest::class); }
}
