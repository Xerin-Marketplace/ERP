<?php

namespace Modules\Tender\Models;

use Illuminate\Database\Eloquent\Model;

class TenderTransition extends Model
{
    protected $table = 'tender_transitions';
    protected $guarded = [];
    protected function casts(): array
    {
        return ['scores' => 'array', 'submitted_at' => 'datetime', 'verified_at' => 'datetime',
            'decided_at' => 'datetime', 'signed_at' => 'date', 'issued_at' => 'date',
            'expires_at' => 'date', 'required_until' => 'date', 'starts_at' => 'date',
            'ends_at' => 'date', 'due_date' => 'date', 'event_date' => 'date',
            'valid_until' => 'date', 'selected' => 'boolean', 'mandatory' => 'boolean',
            'technically_compliant' => 'boolean'];
    }
    public function tender() { return $this->belongsTo(Tender::class); }
}
