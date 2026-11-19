<?php

namespace Modules\Employee\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Employee\Models\JobGrade;
use Modules\Employee\Models\JobPosition;

class StructureService
{
    public function __construct(private AuditService $audit) {}

    public function saveGrade(array $data, ?JobGrade $grade = null): JobGrade
    {
        $grade ??= new JobGrade;
        $grade->fill($data)->save();
        $this->audit->log($grade->wasRecentlyCreated ? 'grade.created' : 'grade.updated', $grade, new: $data);

        return $grade;
    }

    public function savePosition(array $data, ?JobPosition $position = null): JobPosition
    {
        // Position hierarchy: department must belong to the position's company
        if (! empty($data['department_id'])) {
            $dept = \Modules\Organization\Models\Department::find($data['department_id']);
            if (! $dept || $dept->company_id !== (int) ($data['company_id'] ?? $position?->company_id)) {
                throw ValidationException::withMessages(['department_id' => 'Department does not belong to the position company.']);
            }
        }
        // Prevent a position reporting to itself
        if ($position && ($data['reports_to_position_id'] ?? null) == $position->id) {
            throw ValidationException::withMessages(['reports_to_position_id' => 'A position cannot report to itself.']);
        }

        return DB::transaction(function () use ($data, $position) {
            $position ??= new JobPosition;
            $position->fill($data)->save();
            $this->audit->log($position->wasRecentlyCreated ? 'position.created' : 'position.updated', $position, new: $data);

            return $position;
        });
    }
}
