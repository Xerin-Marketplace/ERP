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

class LeaveTransaction extends Model
{
    protected $table = 'leave_transactions';
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['days decimal:1'];
    }

    public function balance(): BelongsTo { return $this->belongsTo(LeaveBalance::class, 'leave_balance_id'); }
    public function request(): BelongsTo { return $this->belongsTo(LeaveRequest::class, 'leave_request_id'); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
