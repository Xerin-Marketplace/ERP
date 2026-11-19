<?php

namespace Modules\Identity\Models;

use App\Models\ManagedFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationDocument extends Model
{
    protected $guarded = ['id'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(RegistrationApplication::class, 'registration_application_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(ManagedFile::class, 'managed_file_id');
    }
}
