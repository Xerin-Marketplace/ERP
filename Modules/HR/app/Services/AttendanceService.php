<?php

namespace Modules\HR\Services;

use App\Models\User;
use App\Services\AuditService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Employee\Models\Employee;
use Modules\HR\Models\AttendanceCorrection;
use Modules\HR\Models\AttendanceEvent;
use Modules\HR\Models\AttendanceRecord;
use Modules\HR\Models\ShiftAssignment;
use Modules\HR\Models\ShiftDefinition;
use Modules\HR\Notifications\HRNotification;

class AttendanceService
{
    public function __construct(
        private AuditService $audit,
        private HRNumberService $numbers,
    ) {}

    /**
     * Today's record for an employee — find or create lazily (unique on employee+work_date).
     */
    public function recordFor(Employee $employee, ?Carbon $date = null): AttendanceRecord
    {
        $date ??= now();

        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('work_date', $date->toDateString())->first();
        if (! $record) {
            $record = new AttendanceRecord(['employee_id' => $employee->id, 'work_date' => $date->toDateString()]);
            $record->fill([
                'company_id' => $employee->company_id,
                'branch_id' => $employee->branch_id,
                'department_id' => $employee->department_id,
                'shift_id' => $this->shiftFor($employee, $date)?->id,
                'status' => 'present',
                'source' => 'web',
            ]);
            $record->reference = $this->numbers->for('attendance');
            $record->save();
        }

        return $record;
    }

    public function shiftFor(Employee $employee, Carbon $date): ?ShiftDefinition
    {
        return ShiftAssignment::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', $date->toDateString())
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date->toDateString()))
            ->latest('effective_from')->first()?->shift;
    }

    public function clockIn(Employee $employee, ?User $actor = null, string $source = 'web'): AttendanceRecord
    {
        return DB::transaction(function () use ($employee, $actor, $source) {
            $record = $this->recordFor($employee);
            if ($record->clock_in_at) {
                throw ValidationException::withMessages(['clock_in' => 'Already clocked in for '.$record->work_date->toDateString().'.']);
            }

            $now = now();
            $record->clock_in_at = $now;
            $record->source = $source;
            $record->late_minutes = $this->lateMinutes($record, $now);
            if ($record->late_minutes > 0) $record->status = 'late';
            $record->save();

            $this->event($record, 'clock_in', $now, $actor, $source);
            $this->audit->log('attendance.clock_in', $record, new: ['at' => $now->toIso8601String(), 'late_minutes' => $record->late_minutes]);

            return $record->fresh();
        });
    }

    public function clockOut(Employee $employee, ?User $actor = null, string $source = 'web'): AttendanceRecord
    {
        return DB::transaction(function () use ($employee, $actor, $source) {
            $record = AttendanceRecord::where('employee_id', $employee->id)
                ->whereNotNull('clock_in_at')->whereNull('clock_out_at')
                ->latest('work_date')->lockForUpdate()->first();

            if (! $record) {
                throw ValidationException::withMessages(['clock_out' => 'No open attendance session. Clock in first.']);
            }

            $now = now();
            $record->clock_out_at = $now;
            $record->worked_minutes = max(0, (int) $record->clock_in_at->diffInMinutes($now) - $record->break_minutes);
            $record->early_departure_minutes = $this->earlyDepartureMinutes($record, $now);
            $record->overtime_minutes = $this->overtimeMinutes($record, $now);
            $record->save();

            $this->event($record, 'clock_out', $now, $actor, $source);
            $this->audit->log('attendance.clock_out', $record, new: ['worked_minutes' => $record->worked_minutes]);

            return $record->fresh();
        });
    }

    public function breakStart(Employee $employee, ?User $actor = null): AttendanceEvent
    {
        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->whereNotNull('clock_in_at')->whereNull('clock_out_at')
            ->latest('work_date')->firstOrFail();

        $openBreaks = $record->events()->where('type', 'break_start')->count()
            - $record->events()->where('type', 'break_end')->count();
        if ($openBreaks > 0) {
            throw ValidationException::withMessages(['break' => 'A break is already in progress.']);
        }

        return $this->event($record, 'break_start', now(), $actor, $record->source);
    }

    public function breakEnd(Employee $employee, ?User $actor = null): AttendanceEvent
    {
        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->whereNotNull('clock_in_at')->whereNull('clock_out_at')
            ->latest('work_date')->lockForUpdate()->firstOrFail();

        $open = $record->events()->where('type', 'break_start')->latest('occurred_at')->first();
        $ended = $record->events()->where('type', 'break_end')->count();
        $started = $record->events()->where('type', 'break_start')->count();

        if (! $open || $ended >= $started) {
            throw ValidationException::withMessages(['break' => 'No open break to end.']);
        }

        $event = $this->event($record, 'break_end', now(), $actor, $record->source);
        $record->increment('break_minutes', (int) $open->occurred_at->diffInMinutes(now()));

        return $event;
    }

    /**
     * Minutes late relative to the employee's shift start (grace included). 0 if no shift.
     */
    public function lateMinutes(AttendanceRecord $record, Carbon $clockIn): int
    {
        $shift = $record->shift;
        if (! $shift) return 0;

        $start = $record->work_date->copy()->setTimeFromTimeString($shift->start_time->format('H:i:s'));
        $graceEnd = $start->copy()->addMinutes($shift->grace_minutes);

        return $clockIn->gt($graceEnd) ? (int) $start->diffInMinutes($clockIn) : 0;
    }

    public function earlyDepartureMinutes(AttendanceRecord $record, Carbon $clockOut): int
    {
        $shift = $record->shift;
        if (! $shift) return 0;

        $end = $this->shiftEnd($record);
        return $clockOut->lt($end) ? (int) $clockOut->diffInMinutes($end) : 0;
    }

    public function overtimeMinutes(AttendanceRecord $record, Carbon $clockOut): int
    {
        $shift = $record->shift;
        if (! $shift) return 0;

        $end = $this->shiftEnd($record);
        return $clockOut->gt($end) ? (int) $end->diffInMinutes($clockOut) : 0;
    }

    /** Shift end on work_date; +1 day for overnight shifts. */
    public function shiftEnd(AttendanceRecord $record): Carbon
    {
        $shift = $record->shift;
        $end = $record->work_date->copy()->setTimeFromTimeString($shift->end_time->format('H:i:s'));
        if ($shift->overnight || $shift->end_time->format('H:i:s') <= $shift->start_time->format('H:i:s')) {
            $end->addDay();
        }
        return $end;
    }

    /** Request a correction (missing punch / time adjustment). */
    public function requestCorrection(Employee $employee, array $data): AttendanceCorrection
    {
        return DB::transaction(function () use ($employee, $data) {
            $correction = new AttendanceCorrection([
                'attendance_id' => $data['attendance_id'] ?? null,
                'employee_id' => $employee->id,
                'work_date' => $data['work_date'],
                'type' => $data['type'],
                'requested_clock_in' => $data['requested_clock_in'] ?? null,
                'requested_clock_out' => $data['requested_clock_out'] ?? null,
                'reason' => $data['reason'],
            ]);
            $correction->reference = $this->numbers->for('correction');
            $correction->save();

            $this->audit->log('attendance.correction_requested', $correction);
            $this->notifyApprovers($employee, "Attendance correction {$correction->reference} needs review");

            return $correction->fresh();
        });
    }

    public function decideCorrection(AttendanceCorrection $correction, User $decider, string $decision, ?string $note = null): AttendanceCorrection
    {
        abort_unless($decider->can('attendance.approve') || $decider->can('attendance.correct'), 403);
        if ($correction->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'This correction was already decided.']);
        }
        if ($decision === 'rejected' && ! $note) {
            throw ValidationException::withMessages(['note' => 'A reason is required to reject.']);
        }

        return DB::transaction(function () use ($correction, $decider, $decision, $note) {
            $correction->update(['status' => $decision, 'decided_by' => $decider->id, 'decided_at' => now(), 'decision_note' => $note]);

            if ($decision === 'approved') {
                $this->applyCorrection($correction);
            }

            $correction->employee->user?->notify(new HRNotification(
                "Correction {$correction->reference} {$decision}",
                $note ?? "Your attendance correction was {$decision}."
            ));
            $this->audit->log("attendance.correction_{$decision}", $correction);

            return $correction->fresh();
        });
    }

    private function applyCorrection(AttendanceCorrection $c): void
    {
        $record = $c->attendance ?? AttendanceRecord::where('employee_id', $c->employee_id)
            ->whereDate('work_date', $c->work_date->toDateString())->first();
        if (! $record) {
            $record = new AttendanceRecord(['employee_id' => $c->employee_id, 'work_date' => $c->work_date->toDateString()]);
            $record->fill([
                'company_id' => $c->employee->company_id,
                'branch_id' => $c->employee->branch_id,
                'department_id' => $c->employee->department_id,
                'shift_id' => $this->shiftFor($c->employee, $c->work_date)?->id,
                'status' => 'present', 'source' => 'manual',
            ]);
            $record->reference = $this->numbers->for('attendance');
            $record->save();
        }

        if ($c->requested_clock_in) {
            $record->clock_in_at = $c->work_date->copy()->setTimeFromTimeString($c->requested_clock_in);
        }
        if ($c->requested_clock_out) {
            $record->clock_out_at = $c->work_date->copy()->setTimeFromTimeString($c->requested_clock_out);
            if ($record->shift && $record->shift->overnight) {
                $record->clock_out_at->addDay();
            }
        }
        if ($record->clock_in_at && $record->clock_out_at) {
            $record->worked_minutes = max(0, (int) $record->clock_in_at->diffInMinutes($record->clock_out_at) - $record->break_minutes);
        }
        $record->verification = 'corrected';
        $record->save();
    }

    private function event(AttendanceRecord $record, string $type, Carbon $at, ?User $actor, string $source): AttendanceEvent
    {
        return $record->events()->create([
            'employee_id' => $record->employee_id,
            'type' => $type,
            'occurred_at' => $at,
            'source' => $source,
            'recorded_by' => $actor?->id,
        ]);
    }

    /** Daily summary rows for a company scope. */
    public function summary(Carbon $date, ?int $companyId = null): Collection
    {
        return AttendanceRecord::with('employee:id,first_name,last_name,employee_number')
            ->where('work_date', $date->toDateString())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get();
    }

    /** Monthly aggregates per employee. */
    public function monthly(int $year, int $month, Employee $employee): array
    {
        $rows = AttendanceRecord::where('employee_id', $employee->id)
            ->whereYear('work_date', $year)->whereMonth('work_date', $month)->get();

        return [
            'days' => $rows->count(),
            'present' => $rows->whereIn('status', ['present', 'late'])->count(),
            'late' => $rows->where('status', 'late')->count(),
            'absent' => $rows->where('status', 'absent')->count(),
            'worked_minutes' => $rows->sum('worked_minutes'),
            'overtime_minutes' => $rows->sum('overtime_minutes'),
            'late_minutes' => $rows->sum('late_minutes'),
        ];
    }

    /** Scoped list query for index/API. */
    public function query(User $viewer)
    {
        $q = AttendanceRecord::with(['employee.user', 'shift'])->latest('work_date');

        if ($viewer->can('attendance.view') || $viewer->can('hr.dashboard.view') || $viewer->can('attendance.approve')) {
            $own = $viewer->employee;
            if (! $viewer->can('hr.dashboard.view') && $own?->company_id) {
                $q->where('company_id', $own->company_id);
            }
            return $q; // HR view → company-scoped (or all for group HR)
        }

        // Self-service: own records only
        return $q->where('employee_id', $viewer->employee?->id ?? -1);
    }

    private function notifyApprovers(Employee $employee, string $message): void
    {
        $managers = User::permission('attendance.approve')->get();
        foreach ($managers as $m) {
            $m->notify(new HRNotification('Attendance correction', $message));
        }
    }
}
