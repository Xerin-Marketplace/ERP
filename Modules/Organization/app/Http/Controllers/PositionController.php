<?php

namespace Modules\Organization\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use Modules\Employee\Models\JobGrade;
use Modules\Employee\Models\JobPosition;
use Modules\Organization\Http\Requests\StorePositionRequest;
use Modules\Organization\Http\Requests\UpdatePositionRequest;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;

/**
 * Positions = organizational job titles (job_positions — Employee module table).
 * Single source of truth: no duplicate positions table exists.
 */
class PositionController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', JobPosition::class);

        $positions = JobPosition::with(['company:id,legal_name', 'department:id,name,branch_id'])
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $s = $request->input('search');
                $q->where('title', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%");
            }))
            ->when($request->filled('company'), fn ($q) => $q->where('company_id', $request->input('company')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy('title')->paginate(15)->withQueryString();

        return view('organization::positions.index', [
            'positions' => $positions,
            'companies' => Company::orderBy('legal_name')->get(['id', 'legal_name']),
        ]);
    }

    public function create()
    {
        $this->authorize('create', JobPosition::class);

        return view('organization::positions.create', $this->formData());
    }

    public function store(StorePositionRequest $request)
    {
        JobPosition::create($request->validated() + ['status' => 'active']);

        return redirect()->route('organization.positions.index')
            ->with('success', 'Position created.');
    }

    public function edit(JobPosition $position)
    {
        $this->authorize('update', $position);

        return view('organization::positions.edit', [
            'position' => $position,
            ...$this->formData(),
        ]);
    }

    public function update(UpdatePositionRequest $request, JobPosition $position)
    {
        $this->authorize('update', $position);
        $position->update($request->validated());

        return redirect()->route('organization.positions.index')
            ->with('success', 'Position updated.');
    }

    public function status(Request $request, JobPosition $position)
    {
        $this->authorize('manage', $position);
        $request->validate(['status' => ['required', 'in:active,inactive']]);
        $position->update(['status' => $request->input('status')]);

        return back()->with('success', "Position {$request->input('status')}.");
    }

    private function formData(): array
    {
        return [
            'companies' => Company::active()->orderBy('legal_name')->get(['id', 'legal_name']),
            'departments' => Department::orderBy('name')->get(['id', 'name', 'company_id', 'branch_id']),
            'grades' => JobGrade::orderBy('level')->get(['id', 'name']),
            'parents' => JobPosition::orderBy('title')->get(['id', 'title', 'company_id']),
        ];
    }
}
