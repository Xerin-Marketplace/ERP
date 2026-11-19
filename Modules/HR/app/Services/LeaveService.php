<?php

namespace Modules\HR\Services;

use App\Models\User;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Employee\Models\Employee;
use Modules\HR\Models\HRApproval;
use Modules\HR\Models\LeaveBalance;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Models\LeaveType;
use Modules\HR\Notifications\HRNotification;

class LeaveService
{
    public function __construct(
        private AuditService $audit,
        private HRNumberService $numbers,
    ) {}

    /** Ensure the employee's balances exist for the year (idempotent). */
    public function ensureBalances(Employee $employee, int $year): void
    {
        foreach (LeaveType::where('is_active', true)->get() as $type) {
            LeaveBalance::firstOrCreate(
                ['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => $year],
                ['entitled' => $this->entitlement($employee, $type)]
            );
        }
    }

    /** Entitlement considering eligibility rules (e.g. gender, service months). */
    public function entitlement(Employee $employee, LeaveType $type): float
    {
        $rules = $type->eligibility ?? [];
        if (isset($rules['min_service_months'])) {
            $months = $employee->start_date ? $employee->start_date->diffInMonths(now()) : 0;
            if ($months < $rules['min_service_months']) return 0;
        }
        if (isset($rules['gender']) && $employee->personal?->gender !== $rules['gender']) return 0;

        return (float) $type->annual_days;
    }

    /** Whole-working-day count inclusive, skipping weekends by default. */
    public function days(Carbon $from, Carbon $to, bool $weekends = false): float
    {
        $days = 0;
        foreach ($from->toPeriod($to) as $d) {
            if ($weekends || ! $d->isWeekend()) $days++;
        }
        return max(0, $days);
    }

    public function balance(Employee $employee, LeaveType $type, ?int $year = null): LeaveBalance
    {
        $year ??= (int) now()->format('Y');
        $this->ensureBalances($employee, $year);
        return LeaveBalance::where(['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => $year])->firstOrFail();
    }

    public function request(Employee $employee, array $data, ?User $actor = null): LeaveRequest
    {
        $type = LeaveType::whereKey($data['leave_type_id'])->where('is_active', true)->firstOrFail();
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        if ($end->lt($start)) {
            throw ValidationException::withMessages(['end_date' => 'End date cannot precede the start date.']);
        }

        $days = ($data['half_day'] ?? false) ? 0.5 : $this->days($start, $end);

        if ($type->max_consecutive_days && $days > $type->max_consecutive_days) {
            throw ValidationException::withMessages(['days' => "Maximum {$type->max_consecutive_days} consecutive days for {$type->name}."]);
        }
        if ($type->requires_document && empty($data['attachment_file_id'])) {
            throw ValidationException::withMessages(['attachment' => "{$type->name} requires a supporting document."]);
        }

        $balance = $this->balance($employee, $type, (int) $start->format('Y'));
        if ($balance->available < $days) {
            throw ValidationException::withMessages(['days' => "Insufficient balance: {$balance->available} day(s) available, {$days} requested."]);
        }

        // Overlap guard: any pending/approved request touching the range
        $overlap = LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['dates' => 'You already have a leave request overlapping these dates.']);
        }

        return DB::transaction(function () use ($employee, $type, $start, $end, $days, $data, $balance, $actor) {
            $request = new LeaveRequest([
                'employee_id' => $employee->id,
                'leave_type_id' => $type->id,
                'start_date' => $start, 'end_date' => $end,
                'days' => $days,
                'half_day' => $data['half_day'] ?? false,
                'reason' => $data['reason'] ?? null,
                'attachment_file_id' => $data['attachment_file_id'] ?? null,
            ]);
            $request->reference = $this->numbers->for('leave');
            $request->save();

            $balance->increment('pending', $days);
            $balance->transactions()->create([
                'leave_request_id' => $request->id, 'type' => 'debit',
                'days' => -$days, 'note' => 'Pending request', 'actor_id' => $actor?->id,
            ]);

            $this->openApprovals($request, $type->approval_chain ?: ['manager']);
            $this->audit->log('leave.requested', $request, new: ['days' => $days, 'type' => $type->code]);
            $this->notifyApprovers($request);

            return $request->fresh();
        });
    }

    public function openApprovals(LeaveRequest $request, array $chain): void
    {
        foreach ($chain as $i => $role) {
            $request->approvals()->create(['step' => $i + 1, 'approver_role' => $role]);
        }
    }

    public function currentStep(LeaveRequest $request): ?HRApproval
    {
        return $request->approvals()->where('status', 'pending')->orderBy('step')->first();
    }

    public function decide(LeaveRequest $request, User $decider, string $decision, ?string $note = null): LeaveRequest
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'This request was already decided.']);
        }
        if ($request->employee->user_id === $decider->id) {
            throw ValidationException::withMessages(['decision' => 'You cannot decide your own leave request.']);
        }
        if ($decision === 'rejected' && ! $note) {
            throw ValidationException::withMessages(['note' => 'A reason is required to reject leave.']);
        }

        $step = $this->currentStep($request);
        if (! $step) {
            throw ValidationException::withMessages(['status' => 'No pending approval step.']);
        }

        // Authorization: matching role permission or catch-all leave.approve/leave.manage
        $rolePerm = 'leave.approve';
        abort_unless($decider->can($rolePerm) || $decider->can('leave.manage'), 403);

        return DB::transaction(function () use ($request, $decider, $decision, $note, $step) {
            $step->update([
                'status' => $decision === 'approved' ? 'approved' : 'rejected',
                'approver_id' => $decider->id, 'note' => $note, 'decided_at' => now(),
            ]);

            if ($decision === 'rejected') {
                $request->approvals()->where('status', 'pending')->update(['status' => 'skipped']);
                $this->releaseBalance($request, 'reversal', 'Rejected');
                $request->update(['status' => 'rejected', 'decided_by' => $decider->id, 'decided_at' => now(), 'decision_note' => $note]);
            } elseif (! $this->currentStep($request->fresh())) {
                // final approval consumed the balance
                $balance = $this->balance($request->employee, $request->type, (int) $request->start_date->format('Y'));
                $balance->decrement('pending', $request->days);
                $balance->increment('used', $request->days);
                $balance->transactions()->create([
                    'leave_request_id' => $request->id, 'type' => 'debit',
                    'days' => $request->days, 'note' => 'Leave approved', 'actor_id' => $decider->id,
                ]);
                $request->update(['status' => 'approved', 'decided_by' => $decider->id, 'decided_at' => now(), 'decision_note' => $note]);
            }

            $request->employee->user?->notify(new HRNotification(
                "Leave {$request->reference} {$request->fresh()->status}",
                $note ?? "Your {$request->type->name} request ({$request->days}d) was {$request->fresh()->status}."
            ));
            $this->audit->log("leave.{$request->fresh()->status}", $request, new: ['days' => $request->days]);

            return $request->fresh();
        });
    }

    public function cancel(LeaveRequest $request, User $actor): LeaveRequest
    {
        $isOwner = $request->employee->user_id === $actor->id;
        abort_unless($isOwner || $actor->can('leave.manage'), 403);
        abort_if($request->status === 'cancelled' || $request->status === 'rejected', 422, 'Nothing to cancel.');

        return DB::transaction(function () use ($request, $actor) {
            if ($request->status === 'pending') {
                $this->releaseBalance($request, 'reversal', 'Cancelled while pending');
            } elseif ($request->status === 'approved') {
                $balance = $this->balance($request->employee, $request->type, (int) $request->start_date->format('Y'));
                $balance->decrement('used', $request->days);
                $balance->transactions()->create([
                    'leave_request_id' => $request->id, 'type' => 'credit',
                    'days' => $request->days, 'note' => 'Approved leave cancelled', 'actor_id' => $actor->id,
                ]);
            }
            $request->approvals()->where('status', 'pending')->update(['status' => 'cancelled']);
            $request->update(['status' => 'cancelled']);
            $this->audit->log('leave.cancelled', $request);

            return $request->fresh();
        });
    }

    private function releaseBalance(LeaveRequest $request, string $type, string $note): void
    {
        $balance = $this->balance($request->employee, $request->type, (int) $request->start_date->format('Y'));
        $balance->decrement('pending', $request->days);
        $balance->transactions()->create([
            'leave_request_id' => $request->id, 'type' => $type,
            'days' => $request->days, 'note' => $note,
        ]);
    }

    public function notifyApprovers(LeaveRequest $request): void
    {
        User::permission(['leave.approve', 'leave.manage'])->get()
            ->each(fn ($u) => $u->notify(new HRNotification(
                "Leave request {$request->reference}",
                "{$request->employee->full_name} requested {$request->days}d {$request->type->name}."
            )));
    }

    public function query(User $viewer)
    {
        $q = LeaveRequest::with(['employee.user', 'type', 'approvals'])->latest('id');
        if ($viewer->can('leave.manage') || $viewer->can('hr.dashboard.view')) return $q;
        if ($viewer->can('leave.approve') || $viewer->can('leave.view')) {
            $own = $viewer->employee;
            return $q->where(fn ($w) => $w->where('employee_id', $own?->id ?? -1)
                ->orWhereHas('employee', fn ($e) => $e->where('department_id', $own?->department_id ?? -1)));
        }
        return $q->where('employee_id', $viewer->employee?->id ?? -1);
    }

    /** Requests awaiting a decision visible to this approver. */
    public function pendingFor(User $approver)
    {
        return LeaveRequest::with(['employee.user', 'type'])
            ->where('status', 'pending')
            ->where('employee_id', '!=', $approver->employee?->id ?? -1)
            ->whereHas('approvals', fn ($q) => $q->where('status', 'pending')
                ->whereNotExists(fn ($w) => $w->selectRaw(1)
                    ->from('hr_approvals as earlier')
                    ->whereColumn('earlier.approvable_id', 'hr_approvals.approvable_id')
                    ->whereColumn('earlier.approvable_type', 'hr_approvals.approvable_type')
                    ->where('earlier.status', 'pending')
                    ->whereColumn('earlier.step', '<', 'hr_approvals.step')))
            ->latest('id');
    }
}
