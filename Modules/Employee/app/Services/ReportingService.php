<?php

namespace Modules\Employee\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\ReportingRelationship;

class ReportingService
{
    public function __construct(private AuditService $audit, private EmployeeService $employees) {}

    /**
     * Set (or replace) the reporting manager for an employee.
     * Ends any current relationship of the same type — never overwrites.
     */
    public function assignManager(Employee $employee, Employee $manager, string $type, ?int $actorId, ?string $effectiveStart = null): ReportingRelationship
    {
        if ($employee->id === $manager->id) {
            throw ValidationException::withMessages(['manager_employee_id' => 'An employee cannot manage themselves.']);
        }

        if ($this->createsCycle($employee->id, $manager->id)) {
            throw ValidationException::withMessages(['manager_employee_id' => 'This assignment would create a circular reporting relationship.']);
        }

        return DB::transaction(function () use ($employee, $manager, $type, $actorId, $effectiveStart) {
            $employee->reportingRelationships()->current()->where('type', $type)
                ->update(['effective_end' => $effectiveStart ?? now()->toDateString()]);

            $rel = $employee->reportingRelationships()->create([
                'manager_employee_id' => $manager->id,
                'type' => $type,
                'effective_start' => $effectiveStart ?? now()->toDateString(),
            ]);

            if ($type === 'direct') {
                $employee->update(['manager_employee_id' => $manager->id]);
            }

            $this->employees->record($employee, 'reporting.changed', "Reporting manager changed to {$manager->full_name} ({$type})", [
                'manager_employee_id' => $manager->id, 'type' => $type,
            ], $actorId);
            $this->audit->log('employee.reporting_changed', $rel, new: [
                'employee_id' => $employee->id, 'manager_employee_id' => $manager->id, 'type' => $type,
            ]);

            return $rel;
        });
    }

    /** Walk the manager chain upward — a cycle exists if we reach $employeeId. */
    public function createsCycle(int $employeeId, int $proposedManagerId): bool
    {
        $seen = [$employeeId => true];
        $current = $proposedManagerId;

        while ($current && ! isset($seen[$current])) {
            $seen[$current] = true;
            $current = Employee::whereKey($current)->value('manager_employee_id');
        }

        return $current === $employeeId;
    }

    /** Current direct reports for an employee. */
    public function directReports(Employee $manager): \Illuminate\Support\Collection
    {
        return ReportingRelationship::with('employee.personal', 'employee.position:id,title')
            ->where('manager_employee_id', $manager->id)->current()->get()
            ->pluck('employee')->filter()->values();
    }

    /** Chain of managers above the employee (for profile display). */
    public function chain(Employee $employee, int $maxDepth = 10): array
    {
        $chain = [];
        $current = $employee;
        while ($current->manager_employee_id && count($chain) < $maxDepth) {
            $current = $current->manager()->with('personal', 'position:id,title')->first();
            if (! $current) break;
            $chain[] = $current;
        }
        return $chain;
    }
}
