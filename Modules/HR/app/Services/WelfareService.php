<?php

namespace Modules\HR\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Modules\Employee\Models\Employee;
use Modules\HR\Models\WelfareRecord;

class WelfareService
{
    public function __construct(
        private AuditService $audit,
        private HRNumberService $numbers,
    ) {}

    public function record(Employee $employee, array $data, User $actor): WelfareRecord
    {
        abort_unless($actor->can('welfare.manage'), 403);

        return DB::transaction(function () use ($employee, $data) {
            $w = new WelfareRecord($data + ['employee_id' => $employee->id]);
            $w->reference = $this->numbers->for('welfare');
            $w->save();
            $this->audit->log('welfare.recorded', $w, new: ['type' => $w->type, 'title' => $w->title]);
            return $w;
        });
    }

    public function query(User $viewer)
    {
        $q = WelfareRecord::with('employee.user')->latest('id');
        if ($viewer->can('welfare.manage') || $viewer->can('hr.dashboard.view')) return $q;
        return $q->where('employee_id', $viewer->employee?->id ?? -1);
    }
}
