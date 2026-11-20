<?php
namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CommercialPartner extends Model
{
    protected $fillable = ['reference', 'type', 'name', 'legal_name', 'contact_name', 'phone', 'email',
        'registration_ref', 'territories', 'commission_terms', 'bank_details', 'manager_id', 'branch_id',
        'contract_id', 'status'];
    protected $casts = ['territories' => 'array', 'commission_terms' => 'array'];

    const TYPES = ['agent', 'broker'];
    const TRANSITIONS = [
        'registered'           => ['pending_verification', 'rejected'],
        'pending_verification' => ['under_review', 'rejected'],
        'under_review'         => ['approved', 'rejected'],
        'approved'             => ['active', 'suspended'],
        'active'               => ['suspended', 'terminated'],
        'suspended'            => ['active', 'terminated'],
        'rejected'             => [], 'terminated' => [],
    ];

    public function manager()       { return $this->belongsTo(\App\Models\User::class, 'manager_id'); }
    public function contract()      { return $this->belongsTo(CommercialContract::class); }
    public function acquisitions()  { return $this->hasMany(CustomerAcquisition::class, 'partner_id'); }
    public function commissions()   { return $this->hasMany(CommissionRecord::class, 'beneficiary_id')
        ->where('beneficiary_type', $this->type); }

    public function canTransitionTo(string $s): bool { return in_array($s, self::TRANSITIONS[$this->status] ?? [], true); }
    public function transitionTo(string $s): void
    {
        if (!$this->canTransitionTo($s)) throw new InvalidArgumentException("Partner {$this->reference} cannot {$this->status}→{$s}");
        $this->update(['status' => $s]);
    }
    public function isCommissionable(): bool { return $this->status === 'active'; }
}
