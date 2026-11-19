<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Services\EmployeeService;

class SelfServiceController extends Controller
{
    public function __construct(private EmployeeService $employees) {}

    public function show(Request $request)
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        $employee->load(['personal', 'company:id,legal_name', 'branch:id,name', 'department:id,name',
            'position:id,title', 'manager.personal', 'currentContract', 'onboarding.tasks',
            'events' => fn ($q) => $q->latest()->limit(20)]);

        return view('employee::self.show', compact('employee'));
    }

    /**
     * Employees may update limited personal fields only — official placement
     * (company/branch/department/position/role) is never editable here.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        abort_unless($user->can('employee.profile.update_own'), 403);
        $employee = $user->employee;
        abort_unless($employee, 404);

        $data = $request->validate([
            'preferred_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'alt_phone' => 'nullable|string|max:30',
            'personal_email' => 'nullable|email',
            'residential_address' => 'nullable|string',
        ]);

        $employee->personal()->updateOrCreate(['employee_id' => $employee->id], $data);
        $this->employees->record($employee, 'profile.self_updated', 'Employee updated own contact details', ['fields' => array_keys($data)], $user->id);
        app(\App\Services\AuditService::class)->log('employee.self_updated', $employee, new: $data);

        return back()->with('success', 'Your details have been updated.');
    }
}
