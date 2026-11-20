<?php
namespace Modules\Portal\Models;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'submitted', 'severity' => 'medium'];
    public const STATUSES = ['submitted','acknowledged','under_investigation','awaiting_evidence','awaiting_department','resolution_proposed','resolved','rejected','reopened','closed'];
    public const TRANSITIONS = [
        'submitted' => ['acknowledged','rejected'],
        'acknowledged' => ['under_investigation','resolution_proposed'],
        'under_investigation' => ['awaiting_evidence','awaiting_department','resolution_proposed'],
        'awaiting_evidence' => ['under_investigation'],
        'awaiting_department' => ['under_investigation'],
        'resolution_proposed' => ['resolved','under_investigation'],
        'resolved' => ['closed','reopened'],
        'rejected' => ['closed','reopened'],
        'reopened' => ['under_investigation'],
        'closed' => [],
    ];
    public function canTransitionTo(string $to): bool { return in_array($to, self::TRANSITIONS[$this->status] ?? [], true); }
    public function events() { return $this->hasMany(ComplaintEvent::class)->latest(); }
    public function customer() { return $this->belongsTo(\Modules\Commercial\Models\Customer::class); }
}
