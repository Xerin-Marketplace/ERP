<?php

namespace Modules\Ticketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCatalogItem extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'required_fields' => 'array', 'approval_chain' => 'array',
        'requires_attachment' => 'boolean', 'requires_approval' => 'boolean', 'is_active' => 'boolean',
    ];

    public function formFields(): HasMany { return $this->hasMany(ServiceFormField::class, 'service_id')->orderBy('sort_order'); }
    public function type(): BelongsTo { return $this->belongsTo(TicketType::class, 'ticket_type_id'); }
    public function category(): BelongsTo { return $this->belongsTo(TicketCategory::class, 'category_id'); }
    public function defaultTeam(): BelongsTo { return $this->belongsTo(TicketTeam::class, 'default_team_id'); }
    public function scopeActive($q) { return $q->where('is_active', true); }
}
