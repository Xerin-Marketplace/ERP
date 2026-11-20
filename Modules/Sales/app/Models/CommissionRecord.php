<?php
namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CommissionRecord extends Model
{
    protected $fillable = ['reference', 'rule_id', 'beneficiary_type', 'beneficiary_id', 'source_type',
        'source_id', 'basis_amount', 'commission_amount', 'currency', 'calc_snapshot', 'status',
        'approved_by', 'settlement_ref'];
    protected $casts = ['calc_snapshot' => 'array'];

    const TRANSITIONS = [
        'calculated'   => ['under_review', 'rejected'],
        'under_review' => ['approved', 'rejected', 'disputed'],
        'approved'     => ['payable', 'reversed'],
        'rejected'     => ['calculated'],
        'payable'      => ['paid', 'reversed'],
        'paid'         => ['reversed'],
        'reversed'     => [], 'disputed' => ['under_review'],
    ];

    public function rule()     { return $this->belongsTo(CommissionRule::class); }
    public function approver() { return $this->belongsTo(\App\Models\User::class, 'approved_by'); }

    public function canTransitionTo(string $s): bool { return in_array($s, self::TRANSITIONS[$this->status] ?? [], true); }
    public function transitionTo(string $s, ?int $actor = null): void
    {
        if (!$this->canTransitionTo($s)) throw new InvalidArgumentException("Commission {$this->reference} cannot {$this->status}→{$s}");
        $attrs = ['status' => $s];
        if ($s === 'approved') $attrs['approved_by'] = $actor;
        $this->update($attrs);
    }
}
