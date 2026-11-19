<?php

namespace Modules\Organization\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\Organization\Enums\EntityStatus;
use Modules\Organization\Http\Requests\StoreDepartmentRequest;
use Modules\Organization\Http\Requests\UpdateDepartmentRequest;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;
use Modules\Organization\Models\DepartmentDefinition;
use Modules\Organization\Services\OrganizationService;

class DepartmentController extends Controller
{
    public function __construct(private OrganizationService $org) {}

    public function index(Request $request)
    {
        $departments = Department::with('company:id,legal_name', 'branch:id,name', 'parent:id,name')
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $s = $request->input('search');
                $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%");
            }))
            ->when($request->filled('company'), fn ($q) => $q->where('company_id', $request->input('company')))
            ->when($request->filled('branch'), fn ($q) => $q->where('branch_id', $request->input('branch')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy('name')
            ->paginate(15)->withQueryString();

        return view('organization::departments.index', [
            'departments' => $departments,
            'companies' => Company::orderBy('legal_name')->get(['id', 'legal_name']),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'statuses' => EntityStatus::cases(),
        ]);
    }

    public function create()
    {
        return view('organization::departments.create', [
            'companies' => Company::active()->orderBy('legal_name')->get(),
            'branches' => Branch::active()->orderBy('name')->get(),
            'parents' => Department::active()->orderBy('name')->get(),
            'definitions' => DepartmentDefinition::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreDepartmentRequest $request)
    {
        $department = $this->org->createDepartment($request->validated(), $request->user()->id);

        return redirect()->route('organization.departments.show', $department)
            ->with('success', "Department {$department->name} created.");
    }

    public function show(Department $department)
    {
        $department->load(['company', 'branch', 'parent', 'children', 'definition', 'creator']);

        return view('organization::departments.show', compact('department'));
    }

    public function edit(Department $department)
    {
        return view('organization::departments.edit', [
            'department' => $department,
            'companies' => Company::active()->orderBy('legal_name')->get(),
            'branches' => Branch::active()->orderBy('name')->get(),
            'parents' => Department::active()->where('id', '!=', $department->id)->orderBy('name')->get(),
            'definitions' => DepartmentDefinition::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $this->org->updateDepartment($department, $request->validated());

        return redirect()->route('organization.departments.show', $department)
            ->with('success', 'Department updated.');
    }

    public function status(Request $request, Department $department)
    {
        $request->validate(['status' => ['required', Rule::enum(EntityStatus::class)]]);
        $status = EntityStatus::from($request->input('status'));
        $perm = match ($status) {
            EntityStatus::Active => 'department.activate',
            EntityStatus::Suspended => 'department.suspend',
            EntityStatus::Archived => 'department.archive',
        };
        abort_unless($request->user()->can($perm), 403);

        $this->org->setDepartmentStatus($department, $status);

        return back()->with('success', "Department {$status->value}.");
    }
}
