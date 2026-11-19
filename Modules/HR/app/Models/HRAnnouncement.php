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

class HRAnnouncement extends Model
{
    protected $table = 'hr_announcements';
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['published_at' => 'date', 'expires_at' => 'date'];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function author(): BelongsTo { return $this->belongsTo(User::class); }
}
