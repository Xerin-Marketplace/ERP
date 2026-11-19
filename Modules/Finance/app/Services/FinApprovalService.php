<?php

namespace Modules\Finance\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Models\FinanceApproval;

/** Polymorphic approval steps for financial documents. Self-approval blocked. */
class FinApprovalService
{
    /** steps: ordered list of role names, e.g. ['finance_manager','finance_director'] */
    public function createChain(Model $approvable, array $steps): void
    {
        $approvable->approvals()->delete();
        foreach (array_values($steps) as $i => $role) {
            FinanceApproval::create([
                'approvable_type' => $approvable->getMorphClass(),
                'approvable_id'   => $approvable->getKey(),
                'step'            => $i + 1,
                'approver_role'   => $role,
                'status'          => 'pending',
            ]);
        }
    }

    public function decide(FinanceApproval $approval, int $userId, bool $approved, ?string $note = null): FinanceApproval
    {
        if ($approval->status !== 'pending') {
            throw ValidationException::withMessages(['approval' => 'This step was already decided.']);
        }
        $approvable = $approval->approvable;
        if ($approvable && ($approvable->created_by ?? null) === $userId) {
            throw ValidationException::withMessages(['approval' => 'You cannot approve your own financial document.']);
        }
        if (! $approved) {
            FinanceApproval::where('approvable_type', $approval->approvable_type)
                ->where('approvable_id', $approval->approvable_id)
                ->where('step', '>', $approval->step)->where('status', 'pending')
                ->update(['status' => 'skipped', 'decided_at' => now()]);
        }
        $approval->update([
            'status'      => $approved ? 'approved' : 'rejected',
            'approver_id' => $userId,
            'note'        => $note,
            'decided_at'  => now(),
        ]);
        return $approval;
    }

    public function isFullyApproved(Model $approvable): bool
    {
        return ! FinanceApproval::where('approvable_type', $approvable->getMorphClass())
            ->where('approvable_id', $approvable->getKey())
            ->where('status', 'pending')->exists()
            && ! FinanceApproval::where('approvable_type', $approvable->getMorphClass())
            ->where('approvable_id', $approvable->getKey())
            ->where('status', 'rejected')->exists();
    }

    public function pending(Model $approvable)
    {
        return FinanceApproval::where('approvable_type', $approvable->getMorphClass())
            ->where('approvable_id', $approvable->getKey())->orderBy('step')->get();
    }
}
