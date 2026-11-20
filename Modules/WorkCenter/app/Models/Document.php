<?php
namespace Modules\WorkCenter\Models;
use Illuminate\Database\Eloquent\Model;
class Document extends Model
{
    protected $table = 'documents';
    protected $guarded = [];
    protected function casts(): array
    {
        return ['sections' => 'array', 'tags' => 'array', 'content' => 'array',
            'period_start' => 'date', 'period_end' => 'date', 'expires_at' => 'date',
            'submitted_at' => 'datetime', 'published_at' => 'datetime',
            'completed_at' => 'datetime', 'starts_at' => 'datetime', 'due_at' => 'datetime',
            'decided_at' => 'datetime', 'requires_review' => 'boolean', 'active' => 'boolean'];
    }
    public function versions() { return $this->hasMany(DocumentVersion::class); }
    public function shares() { return $this->hasMany(DocumentShare::class); }
    public function owner() { return $this->belongsTo(\App\Models\User::class, 'owner_id'); }
    public function file() { return $this->belongsTo(\App\Models\ManagedFile::class, 'file_id'); }
    public function attachable() { return $this->morphTo(); }
}
