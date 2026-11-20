<?php
namespace Modules\WorkCenter\Models;
use Illuminate\Database\Eloquent\Model;
class Report extends Model
{
    protected $table = 'reports';
    protected $guarded = [];
    protected function casts(): array
    {
        return ['sections' => 'array', 'tags' => 'array', 'content' => 'array',
            'period_start' => 'date', 'period_end' => 'date', 'expires_at' => 'date',
            'submitted_at' => 'datetime', 'published_at' => 'datetime',
            'completed_at' => 'datetime', 'starts_at' => 'datetime', 'due_at' => 'datetime',
            'decided_at' => 'datetime', 'requires_review' => 'boolean', 'active' => 'boolean'];
    }
    public function versions() { return $this->hasMany(ReportVersion::class); }
    public function reviews() { return $this->hasMany(ReportReview::class); }
    public function owner() { return $this->belongsTo(\App\Models\User::class, 'owner_id'); }
    public function template() { return $this->belongsTo(ReportTemplate::class); }
    public function category() { return $this->belongsTo(ReportCategory::class); }
    public function scopeVisibleTo($q, \App\Models\User $u) {
        if ($u->hasRole('super-admin')) return $q;
        return $q->where(fn($w) => $w->where('owner_id', $u->id)
            ->orWhere('visibility', 'company')
            ->orWhere(fn($d) => $d->where('visibility', 'branch')->where('branch_id', $u->branch_id))
            ->orWhere(fn($d) => $d->where('visibility', 'department')->where('department_id', $u->department_id)));
    }
}
