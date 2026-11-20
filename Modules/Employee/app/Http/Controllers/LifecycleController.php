<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\OffboardingRecord;
use Modules\Employee\Models\OnboardingRecord;
use Modules\Employee\Models\OnboardingTask;
use Modules\Employee\Services\EmployeeService;
use Modules\Employee\Services\OffboardingService;
use Modules\Employee\Services\OnboardingService;

class LifecycleController extends Controller
{
    public function __construct(
        private OnboardingService $onboarding,
        private OffboardingService $offboarding,
        private EmployeeService $employees,
    ) {}

    /* ------------------- Onboarding ------------------- */

    public function onboardingIndex(Request $request)
    {
        abort_unless($request->user()->canAny(['employee.onboarding.manage', 'employee.view_all']), 403);

        return view('employee::onboarding.index', [
            'records' => OnboardingRecord::with('employee.personal', 'employee.company:id,legal_name', 'tasks')
                ->latest()->paginate(20),
        ]);
    }

    public function startOnboarding(Request $request, Employee $employee)
    {
        abort_unless($request->user()->can('employee.onboarding.manage'), 403);
        $this->onboarding->start($employee, $request->user()->id);

        return back()->with('success', 'Onboarding started.');
    }

    public function updateTask(Request $request, OnboardingTask $task)
    {
        $user = $request->user();
        $isSelf = $user->employee?->id === $task->record->employee_id;
        abort_unless($isSelf || $user->can('employee.onboarding.manage'), 403);

        $request->validate(['status' => 'required|in:pending,in_progress,completed,skipped']);
        $this->onboarding->updateTask($task, $request->status, $user->id);

        return back()->with('success', 'Task updated.');
    }

    /* ------------------- Offboarding ------------------- */

    public function offboardingIndex(Request $request)
    {
        abort_unless($request->user()->canAny(['employee.offboarding.manage', 'employee.view_all']), 403);

        return view('employee::offboarding.index', [
            'records' => OffboardingRecord::with('employee.personal', 'requester:id,name', 'approver:id,name')
                ->latest()->paginate(20),
            'employees' => Employee::where('status', '!=', 'separated')->with('personal')->get(),
            'reasons' => OffboardingRecord::REASONS,
            'canManage' => $request->user()->can('employee.offboarding.manage'),
        ]);
    }

    public function initiateOffboarding(Request $request, Employee $employee)
    {
        abort_unless($request->user()->can('employee.offboarding.manage'), 403);
        $data = $request->validate([
            'reason' => 'required|in:'.implode(',', OffboardingRecord::REASONS),
            'separation_date' => 'required|date',
            'notice_date' => 'nullable|date',
            'handover_notes' => 'nullable|string',
        ]);
        $this->offboarding->initiate($employee, $data, $request->user());

        return back()->with('success', 'Offboarding initiated — pending approval.');
    }

    public function approveOffboarding(Request $request, OffboardingRecord $record)
    {
        abort_unless($request->user()->can('employee.offboarding.manage'), 403);
        $this->offboarding->approve($record, $request->user());

        return back()->with('success', 'Offboarding approved.');
    }

    public function completeOffboarding(Request $request, OffboardingRecord $record)
    {
        abort_unless($request->user()->can('employee.offboarding.manage'), 403);
        $this->offboarding->complete($record, $request->user(), $request->input('checklist', []));

        return back()->with('success', 'Separation completed — ERP access revoked.');
    }

    /* ------------------- Lifecycle status transitions ------------------- */

    /**
     * Auditable status change — reason + actor + effective date are recorded
     * on every transition (suspend / reinstate / resign / terminate / retire).
     */
    public function transition(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'status' => 'required|in:suspended,on_leave,active,on_probation,resigned,terminated,separated,pending_approval',
            'reason' => 'required|string|max:500',
            'effective_at' => 'nullable|date',
        ]);

        $permission = match ($data['status']) {
            'suspended', 'on_leave' => 'employee.suspend',
            'resigned', 'terminated', 'separated' => 'employee.terminate',
            'pending_approval' => 'employee.approve',
            default => 'employee.update',
        };
        abort_unless($request->user()->can($permission), 403);

        $this->employees->transitionStatus(
            $employee, $data['status'], $request->user()->id,
            $data['reason'],
            isset($data['effective_at']) ? new \DateTime($data['effective_at']) : null,
        );

        return back()->with('success', "Employee status changed to {$data['status']}.");
    }
}
