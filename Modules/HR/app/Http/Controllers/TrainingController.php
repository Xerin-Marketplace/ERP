<?php

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Employee\Models\Employee;
use Modules\HR\Models\TrainingEnrollment;
use Modules\HR\Models\TrainingProgram;
use Modules\HR\Services\TrainingService;

class TrainingController extends Controller
{
    public function __construct(private TrainingService $svc) {}

    public function index(Request $request)
    {
        $programs = TrainingProgram::withCount('enrollments')->latest()->get();
        $myEnrollments = $request->user()->employee
            ? TrainingEnrollment::with('program')->where('employee_id', $request->user()->employee->id)->latest()->get()
            : collect();
        $employees = $request->user()->can('training.manage')
            ? Employee::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_number'])
            : collect();

        return view('hr::training.index', compact('programs', 'myEnrollments', 'employees'));
    }

    public function storeProgram(Request $request)
    {
        abort_unless($request->user()->can('training.manage'), 403);
        $data = $request->validate([
            'code' => 'required|string|max:30|unique:training_programs,code',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'provider' => 'nullable|string|max:255',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'capacity' => 'nullable|integer|min:1',
            'certifies' => 'nullable|boolean',
            'cert_validity_months' => 'nullable|integer|min:1',
            'budget' => 'nullable|numeric|min:0',
        ]);
        $this->svc->createProgram($data);
        return back()->with('status', 'Program created.');
    }

    public function enroll(Request $request, TrainingProgram $program)
    {
        $data = $request->validate(['employee_id' => 'required|exists:employees,id']);
        $employee = Employee::findOrFail($data['employee_id']);
        // self-enroll allowed; enrolling others requires manage
        abort_unless($employee->user_id === $request->user()->id || $request->user()->can('training.manage'), 403);
        $this->svc->enroll($program, $employee, $request->user());
        return back()->with('status', 'Enrolled.');
    }

    public function complete(Request $request, TrainingEnrollment $enrollment)
    {
        $data = $request->validate(['score' => 'nullable|integer|min:0|max:100']);
        $this->svc->complete($enrollment, $data['score'] !== null ? (int) $data['score'] : null, $request->user());
        return back()->with('status', 'Enrollment completed.');
    }
}
