<?php
namespace Modules\Portal\Models;
use Illuminate\Database\Eloquent\Model;

class CargoClaim extends Model
{
    protected $table = 'cargo_claims';
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'submitted'];
    protected $casts = ['incident_date' => 'date', 'decision_at' => 'datetime'];
    public const STATUSES = ['submitted','under_review','investigation','additional_evidence','pending_approval','approved','partially_approved','rejected','settlement_pending','settled','closed','reopened'];
    public const TRANSITIONS = [
        'submitted' => ['under_review','rejected'],
        'under_review' => ['investigation','additional_evidence','rejected'],
        'investigation' => ['additional_evidence','pending_approval'],
        'additional_evidence' => ['investigation'],
        'pending_approval' => ['approved','partially_approved','rejected','investigation'],
        'approved' => ['settlement_pending'],
        'partially_approved' => ['settlement_pending'],
        'settlement_pending' => ['settled'],
        'rejected' => ['closed','reopened'],
        'settled' => ['closed'],
        'reopened' => ['investigation'],
        'closed' => [],
    ];
    public function canTransitionTo(string $to): bool { return in_array($to, self::TRANSITIONS[$this->status] ?? [], true); }
    public function events() { return $this->hasMany(ClaimEvent::class, 'claim_id')->latest(); }
    public function customer() { return $this->belongsTo(\Modules\Commercial\Models\Customer::class); }
    public function investigator() { return $this->belongsTo(\App\Models\User::class, 'investigator_id'); }
    public function decider() { return $this->belongsTo(\App\Models\User::class, 'decision_by'); }
}
