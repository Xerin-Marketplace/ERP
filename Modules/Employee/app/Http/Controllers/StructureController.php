<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Models\JobGrade;
use Modules\Employee\Models\JobPosition;
use Modules\Employee\Services\StructureService;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;

class StructureController extends Controller
{
    public function __construct(private StructureService $structure) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->canAny(['employee.position.manage', 'employee.grade.manage', 'employee.view_all']), 403);

        return view('employee::structure.index', [
            'positions' => JobPosition::with('company:id,legal_name', 'department:id,name', 'grade:id,name', 'reportsTo:id,title')->withCount('employees')->orderBy('title')->paginate(20),
            'grades' => JobGrade::orderBy('level')->get(),
            'companies' => Company::where('status', 'active')->orderBy('legal_name')->get(),
            'departments' => Department::where('status', 'active')->orderBy('name')->get(),
            'allPositions' => JobPosition::orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function storePosition(Request $request)
    {
        abort_unless($request->user()->can('employee.position.manage'), 403);
        $data = $request->validate([
            'code' => 'required|string|max:30|unique:job_positions,code',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'company_id' => 'required|exists:companies,id',
            'department_id' => 'nullable|exists:departments,id',
            'job_grade_id' => 'nullable|exists:job_grades,id',
            'reports_to_position_id' => 'nullable|exists:job_positions,id',
            'category' => 'nullable|string|max:60',
            'minimum_qualifications' => 'nullable|string',
            'responsibilities' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);
        $this->structure->savePosition($data);

        return back()->with('success', 'Position created.');
    }

    public function updatePosition(Request $request, JobPosition $position)
    {
        abort_unless($request->user()->can('employee.position.manage'), 403);
        $data = $request->validate([
            'code' => 'required|string|max:30|unique:job_positions,code,'.$position->id,
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'company_id' => 'required|exists:companies,id',
            'department_id' => 'nullable|exists:departments,id',
            'job_grade_id' => 'nullable|exists:job_grades,id',
            'reports_to_position_id' => 'nullable|exists:job_positions,id|not_in:'.$position->id,
            'category' => 'nullable|string|max:60',
            'minimum_qualifications' => 'nullable|string',
            'responsibilities' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);
        $this->structure->savePosition($data, $position);

        return back()->with('success', 'Position updated.');
    }

    public function storeGrade(Request $request)
    {
        abort_unless($request->user()->can('employee.grade.manage'), 403);
        $data = $request->validate([
            'code' => 'required|string|max:30|unique:job_grades,code',
            'name' => 'required|string|max:255',
            'level' => 'required|integer|min:1|max:50',
            'description' => 'nullable|string',
        ]);
        $this->structure->saveGrade($data);

        return back()->with('success', 'Grade created.');
    }

    public function updateGrade(Request $request, JobGrade $grade)
    {
        abort_unless($request->user()->can('employee.grade.manage'), 403);
        $data = $request->validate([
            'code' => 'required|string|max:30|unique:job_grades,code,'.$grade->id,
            'name' => 'required|string|max:255',
            'level' => 'required|integer|min:1|max:50',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $this->structure->saveGrade($data, $grade);

        return back()->with('success', 'Grade updated.');
    }
}
