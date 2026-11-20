<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Organization\Models\Branch;

class PayrollPeriod extends Model
{
    public const STATUSES = ['open', 'processing', 'approved', 'closed'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'closed_at' => 'datetime'];
    }

    public function branch() { return $this->belongsTo(Branch::class); }
    public function runs() { return $this->hasMany(PayrollRun::class); }
    public function closer() { return $this->belongsTo(\App\Models\User::class, 'closed_by'); }
}
