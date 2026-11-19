<?php

namespace Modules\HR\Services;

use App\Models\User;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Employee\Models\Employee;
use Modules\HR\Models\ShiftAssignment;
use Modules\HR\Models\ShiftDefinition;
use Modules\HR\Notifications\HRNotification;

class ShiftService
{
    public function __construct(private AuditService $audit) {}

    public function createDefinition(array $data): ShiftDefinition
    {
        return DB::transaction(function () use ($data) {
            // Overnight auto-detect: end ≤ start means the shift crosses midnight
            if (! isset($data['overnight'])) {
                $data['overnight'] = $data['end_time'] <= $data['start_time'];
            }
            $shift = ShiftDefinition::create($data);
            $this->audit->log('hr.shift_created', $shift, new: $shift->only(['code', 'start_time', 'end_time', 'overnight']));
            return $shift;
        });
    }

    /** Assign; ends any overlapping active assignment for the employee. */
    public function assign(ShiftDefinition $shift, Employee $employee, array $data, User $actor): ShiftAssignment
    {
        $from = Carbon::parse($data['effective_from']);
        $to = isset($data['effective_to']) ? Carbon::parse($data['effective_to']) : null;
        if ($to && $to->lt($from)) {
            throw ValidationException::withMessages(['effective_to' => 'End date cannot precede the start date.']);
        }

        return DB::transaction(function () use ($shift, $employee, $from, $to, $actor, $data) {
            // End overlapping assignments
            ShiftAssignment::where('employee_id', $employee->id)->where('status', 'active')
                ->where('effective_from', '<=', $to?->toDateString() ?? '9999-12-31')
                ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $from->toDateString()))
                ->update(['status' => 'ended', 'effective_to' => $from->copy()->subDay()->toDateString()]);

            $assignment = ShiftAssignment::create([
                'shift_id' => $shift->id,
                'employee_id' => $employee->id,
                'team_id' => $data['team_id'] ?? null,
                'effective_from' => $from,
                'effective_to' => $to,
                'assigned_by' => $actor->id,
            ]);

            $employee->user?->notify(new HRNotification('Shift assigned', "You are now on the {$shift->name} shift ({$shift->start_time->format('H:i')}–{$shift->end_time->format('H:i')})."));
            $this->audit->log('hr.shift_assigned', $assignment, new: ['employee' => $employee->employee_number, 'shift' => $shift->code]);

            return $assignment;
        });
    }

    /** Scheduled shift minutes, handles overnight. */
    public function scheduledMinutes(ShiftDefinition $shift): int
    {
        $start = Carbon::parse($shift->start_time->format('H:i'));
        $end = Carbon::parse($shift->end_time->format('H:i'));
        if ($end <= $start) $end->addDay();
        return (int) $start->diffInMinutes($end) - $shift->break_minutes;
    }
}
