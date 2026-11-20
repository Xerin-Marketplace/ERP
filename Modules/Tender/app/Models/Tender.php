<?php

namespace Modules\Tender\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};
use Modules\Organization\Models\{Branch, Company};

class Tender extends Model
{
    protected $table = 'tender_opportunities';

    protected $fillable = ['company_id','branch_id','reference','buyer_name','buyer_type','category',
        'title','description','location','source','source_url','stage','estimated_value',
        'submitted_value','currency','document_fee','security_required','performance_security_pct',
        'published_at','prebid_meeting_at','clarification_deadline','submission_deadline',
        'validity_days','manager_id','created_by'];

    protected function casts(): array
    {
        return ['submission_deadline' => 'datetime', 'published_at' => 'date',
            'prebid_meeting_at' => 'date', 'clarification_deadline' => 'date'];
    }

    public const STAGES = ['registered','initial_review','bid_decision','preparation',
        'boq','costing','technical_proposal','financial_proposal','internal_review',
        'approval','submission_ready','submitted','evaluation','clarification',
        'negotiation','awarded','unsuccessful','cancelled','contract_signing',
        'contract_active','execution','completed','closed'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function team(): HasMany { return $this->hasMany(TenderTeamMember::class); }
    public function documents(): HasMany { return $this->hasMany(TenderDocument::class); }
    public function requirements(): HasMany { return $this->hasMany(TenderRequirement::class); }
    public function assessment(): HasOne { return $this->hasOne(TenderAssessment::class)->latestOfMany(); }
    public function boqs(): HasMany { return $this->hasMany(TenderBoq::class); }
    public function activeBoq(): HasOne { return $this->hasOne(TenderBoq::class)->latestOfMany(); }
    public function quotations(): HasMany { return $this->hasMany(TenderQuotation::class); }
    public function scenarios(): HasMany { return $this->hasMany(TenderPricingScenario::class); }
    public function securities(): HasMany { return $this->hasMany(TenderSecurity::class); }
    public function approvals(): HasMany { return $this->hasMany(TenderApproval::class); }
    public function submission(): HasOne { return $this->hasOne(TenderSubmission::class)->latestOfMany(); }
    public function events(): HasMany { return $this->hasMany(TenderEvent::class); }
    public function contracts(): HasMany { return $this->hasMany(TenderContract::class); }
    public function transitions(): HasMany { return $this->hasMany(TenderTransition::class); }
}
