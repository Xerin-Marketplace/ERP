<?php
namespace Modules\Arrival\Models;
use Illuminate\Database\Eloquent\Model;

class CustomsCase extends Model
{
    protected $table = 'customs_cases';
    protected $guarded = ['id', 'reference'];
    protected $attributes = ['status' => 'not_started', 'currency' => 'TZS'];
    protected $casts = ['declaration_date' => 'date', 'inspection_required' => 'boolean'];
    public const STATUSES = ['not_started','documents_pending','ready_for_submission','submitted','under_assessment','additional_info_required','inspection_required','payment_pending','payment_recorded','awaiting_release','released','rejected','on_hold','cancelled'];
    public const TRANSITIONS = ['not_started'=>['documents_pending','ready_for_submission'],'documents_pending'=>['ready_for_submission','on_hold'],'ready_for_submission'=>['submitted','documents_pending'],'submitted'=>['under_assessment','additional_info_required','rejected'],'under_assessment'=>['inspection_required','payment_pending','additional_info_required','rejected'],'additional_info_required'=>['under_assessment','submitted'],'inspection_required'=>['payment_pending','additional_info_required','rejected'],'payment_pending'=>['payment_recorded'],'payment_recorded'=>['awaiting_release'],'awaiting_release'=>['released','on_hold'],'on_hold'=>['under_assessment','cancelled'],'released'=>[],'rejected'=>['documents_pending','cancelled'],'cancelled'=>[]];
    public function canTransitionTo(string $to): bool { return in_array($to, self::TRANSITIONS[$this->status] ?? [], true); }
    public function arrival() { return $this->belongsTo(ShipmentArrival::class, 'arrival_id'); }
    public function agent() { return $this->belongsTo(ClearingAgent::class, 'agent_id'); }
    public function events() { return $this->hasMany(CustomsCaseEvent::class, 'case_id')->latest(); }
    public function charges() { return $this->hasMany(CustomsCharge::class, 'case_id'); }
}
