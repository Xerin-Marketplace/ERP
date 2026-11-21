<?php

namespace Modules\HR\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Employee\Models\Employee;
use Modules\HR\Models\OvertimeRequest;
use Modules\HR\Notifications\HRNotification;

class OvertimeService
{
    public function __construct(
        private AuditService $audit,
        private HRNumberService $numbers,
    ) {}

    public function request(Employee $employee, array $data): OvertimeRequest
    {
        if (($data['planned_hours'] ?? 0) <= 0) {
            throw ValidationException::withMessages(['planned_hours' => 'Planned hours must be positive.']);
        }

        return DB::transaction(function () use ($employee, $data) {
            $ot = new OvertimeRequest([
                'employee_id' => $employee->id,
                'work_date' => $data['work_date'],
                'planned_start' => $data['planned_start'] ?? null,
                'planned_end' => $data['planned_end'] ?? null,
                'planned_hours' => $data['planned_hours'],
                'reason' => $data['reason'],
            ]);
            $ot->reference = $this->numbers->for('overtime');
            $ot->save();

            $ot->approvals()->create(['step' => 1, 'approver_role' => 'Branch Manager']);
            $ot->approvals()->create(['step' => 2, 'approver_role' => 'CTO and Admin']);
            $this->audit->log('overtime.requested', $ot, new: ['hours' => $ot->planned_hours]);

            User::permission('overtime.approve')->get()
                ->each(fn ($u) => $u->notify(new HRNotification("Overtime {$ot->reference}", "{$employee->full_name} requested {$ot->planned_hours}h overtime.")));

            return $ot->fresh();
        });
    }

    public function decide(OvertimeRequest $ot, User $decider, string $decision, ?string $note = null): OvertimeRequest
    {
        abort_unless($decider->can('overtime.approve') || $decider->can('overtime.manage'), 403);
        if ($ot->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Already decided.']);
        }
        if ($ot->employee->user_id === $decider->id) {
            throw ValidationException::withMessages(['decision' => 'You cannot approve your own overtime.']);
        }
        if ($decision === 'rejected' && ! $note) {
            throw ValidationException::withMessages(['note' => 'Rejection requires a reason.']);
        }

        $step = $ot->approvals()->where('status', 'pending')->orderBy('step')->firstOrFail();

        return DB::transaction(function () use ($ot, $decider, $decision, $note, $step) {
            $step->update(['status' => $decision, 'approver_id' => $decider->id, 'note' => $note, 'decided_at' => now()]);

            if ($decision === 'rejected') {
                $ot->approvals()->where('status', 'pending')->update(['status' => 'skipped']);
                $ot->update(['status' => 'rejected', 'decided_by' => $decider->id, 'decided_at' => now(), 'decision_note' => $note]);
            } elseif (! $ot->fresh()->approvals()->where('status', 'pending')->exists()) {
                $ot->update(['status' => 'approved', 'decided_by' => $decider->id, 'decided_at' => now(), 'decision_note' => $note]);
            }

            $ot->employee->user?->notify(new HRNotification("Overtime {$ot->reference} {$ot->fresh()->status}", $note ?? "Decision recorded."));
            $this->audit->log("overtime.{$ot->fresh()->status}", $ot);

            return $ot->fresh();
        });
    }

    /** HR verification of actual worked hours post-approval (feeds future payroll). */
    public function verify(OvertimeRequest $ot, User $verifier, float $actualHours): OvertimeRequest
    {
        abort_unless($verifier->can('overtime.manage') || $verifier->can('overtime.approve'), 403);
        abort_if($ot->status !== 'approved', 422, 'Only approved overtime can be verified.');
        abort_if($actualHours <= 0 || $actualHours > 24, 422, 'Actual hours out of range.');

        $ot->update(['actual_hours' => $actualHours, 'status' => 'verified']);
        $this->audit->log('overtime.verified', $ot, new: ['actual_hours' => $actualHours]);
        return $ot->fresh();
    }

    public function query(User $viewer)
    {
        $q = OvertimeRequest::with(['employee.user', 'approvals'])->latest('id');
        if ($viewer->can('overtime.manage') || $viewer->can('hr.dashboard.view')) return $q;
        if ($viewer->can('overtime.approve')) {
            return $q; // approvers see all pending — scoping tightened when org hierarchy matures
        }
        return $q->where('employee_id', $viewer->employee?->id ?? -1);
    }
}
