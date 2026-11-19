<?php

namespace Modules\Ticketing\Models;

use App\Models\ManagedFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    protected $guarded = ['id'];

    public function ticket(): BelongsTo { return $this->belongsTo(Ticket::class); }
    public function file(): BelongsTo { return $this->belongsTo(ManagedFile::class, 'managed_file_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}
