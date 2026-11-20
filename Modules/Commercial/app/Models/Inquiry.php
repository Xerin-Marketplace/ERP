<?php

namespace Modules\Commercial\Models;

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    protected $attributes = ['status' => 'new', 'priority' => 'normal'];
    protected $guarded = ['id', 'reference'];
    protected $casts = ['expected_ship_date' => 'date', 'required_delivery_date' => 'date'];

    public const STATUSES = ['new','received','under_review','awaiting_info','assessment_required','ready_for_quote','quotation_prepared','quotation_sent','accepted','rejected','converted','on_hold','closed'];
    public const CATEGORIES = ['air','express','general','commercial','personal','special'];
    public const SOURCES = ['website','portal','whatsapp','phone','email','walkin','agent','manual'];

    public const TRANSITIONS = [
        'new' => ['received','under_review','on_hold','closed'],
        'received' => ['under_review','awaiting_info','assessment_required','on_hold'],
        'under_review' => ['awaiting_info','assessment_required','ready_for_quote','on_hold'],
        'awaiting_info' => ['under_review','on_hold','closed'],
        'assessment_required' => ['ready_for_quote','rejected','on_hold'],
        'ready_for_quote' => ['quotation_prepared','on_hold'],
        'quotation_prepared' => ['quotation_sent','ready_for_quote'],
        'quotation_sent' => ['accepted','rejected','on_hold'],
        'accepted' => ['converted'],
        'rejected' => ['closed'],
        'on_hold' => ['under_review','closed'],
        'converted' => ['closed'],
        'closed' => [],
    ];

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function missingFields(): array
    {
        return collect([
            'origin_country' => 'Origin country', 'destination_country' => 'Destination country',
            'cargo_category' => 'Cargo category', 'cargo_description' => 'Cargo description',
            'estimated_weight' => 'Estimated weight', 'package_count' => 'Package count',
        ])->filter(fn ($l, $k) => blank($this->$k))->values()->all();
    }

    public function customer() { return $this->belongsTo(Customer::class); }
    public function lead() { return $this->belongsTo(Lead::class); }
    public function branch() { return $this->belongsTo(\Modules\Organization\Models\Branch::class); }
    public function agent() { return $this->belongsTo(\App\Models\User::class, 'agent_id'); }
    public function activities() { return $this->hasMany(InquiryActivity::class)->latest(); }
    public function attachments() { return $this->hasMany(InquiryAttachment::class); }
    public function assessments() { return $this->hasMany(CargoAssessment::class)->latest(); }
    public function latestAssessment() { return $this->hasOne(CargoAssessment::class)->latestOfMany(); }
    public function quotations() { return $this->hasMany(Quotation::class); }

    public function scopeFor($q, $user)
    {
        if ($user->can('commercial.all_branches') || ! $user->currentAssignment?->branch_id) return $q;
        return $q->where(fn ($w) => $w->where('branch_id', $user->currentAssignment->branch_id)->orWhereNull('branch_id'));
    }
}
