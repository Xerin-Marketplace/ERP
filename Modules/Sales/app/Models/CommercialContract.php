<?php
namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CommercialContract extends Model
{
    protected $fillable = ['reference', 'type', 'customer_id', 'partner_id', 'branch_id',
        'start_date', 'expiry_date', 'renewal_terms', 'owner_id', 'terms', 'version',
        'signature_evidence', 'status'];
    protected $casts = ['terms' => 'array', 'start_date' => 'date', 'expiry_date' => 'date'];

    const TYPES = ['corporate','sme','agent','broker','carrier','special_rate','partnership'];
    const TRANSITIONS = [
        'draft'            => ['under_review', 'terminated'],
        'under_review'     => ['pending_approval', 'draft'],
        'pending_approval' => ['approved', 'under_review'],
        'approved'         => ['pending_signature'],
        'pending_signature'=> ['active'],
        'active'           => ['suspended', 'expired', 'terminated', 'renewed'],
        'suspended'        => ['active', 'terminated'],
        'expired'          => ['renewed'],
        'terminated'       => [],
        'renewed'          => [],
    ];

    public function customer()  { return $this->belongsTo(\Modules\Commercial\Models\Customer::class); }
    public function partner()   { return $this->belongsTo(CommercialPartner::class); }
    public function owner()     { return $this->belongsTo(\App\Models\User::class, 'owner_id'); }
    public function versions()  { return $this->hasMany(ContractVersion::class, 'contract_id'); }

    public function canTransitionTo(string $s): bool { return in_array($s, self::TRANSITIONS[$this->status] ?? [], true); }
    public function transitionTo(string $s): void
    {
        if (!$this->canTransitionTo($s)) throw new InvalidArgumentException("Contract {$this->reference} cannot {$this->status}→{$s}");
        if ($s === 'active' && empty($this->signature_evidence))
            throw new InvalidArgumentException('Signature evidence required before activation — unsigned contract cannot be treated as executed');
        $this->update(['status' => $s]);
    }

    public function isExpired(): bool { return $this->expiry_date?->isPast() ?? false; }
}
