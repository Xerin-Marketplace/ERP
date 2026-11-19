<?php

namespace Modules\Organization\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\Organization\Enums\EntityStatus;
use Modules\Organization\Http\Requests\StoreBranchRequest;
use Modules\Organization\Http\Requests\UpdateBranchRequest;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Services\OrganizationService;

class BranchController extends Controller
{
    public function __construct(private OrganizationService $org) {}

    public function index(Request $request)
    {
        $branches = Branch::with('company:id,legal_name')
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $s = $request->input('search');
                $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%");
            }))
            ->when($request->filled('company'), fn ($q) => $q->where('company_id', $request->input('company')))
            ->when($request->filled('region'), fn ($q) => $q->where('region', $request->input('region')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy($request->input('sort', 'name'), $request->input('dir') === 'desc' ? 'desc' : 'asc')
            ->paginate(15)->withQueryString();

        return view('organization::branches.index', [
            'branches' => $branches,
            'companies' => Company::orderBy('legal_name')->get(['id', 'legal_name']),
            'regions' => Branch::whereNotNull('region')->distinct()->orderBy('region')->pluck('region'),
            'statuses' => EntityStatus::cases(),
        ]);
    }

    public function create()
    {
        return view('organization::branches.create', [
            'companies' => Company::active()->orderBy('legal_name')->get(),
            'types' => Branch::TYPES,
        ]);
    }

    public function store(StoreBranchRequest $request)
    {
        $branch = $this->org->createBranch($request->validated(), $request->user()->id);

        return redirect()->route('organization.branches.show', $branch)
            ->with('success', "Branch {$branch->name} created.");
    }

    public function show(Branch $branch)
    {
        $branch->load(['company', 'departments.children', 'locations', 'users:id,name,email,branch_id', 'creator']);

        return view('organization::branches.show', compact('branch'));
    }

    public function edit(Branch $branch)
    {
        return view('organization::branches.edit', [
            'branch' => $branch,
            'companies' => Company::active()->orderBy('legal_name')->get(),
            'types' => Branch::TYPES,
        ]);
    }

    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        $this->org->updateBranch($branch, $request->validated());

        return redirect()->route('organization.branches.show', $branch)
            ->with('success', 'Branch updated.');
    }

    public function status(Request $request, Branch $branch)
    {
        $request->validate(['status' => ['required', Rule::enum(EntityStatus::class)]]);
        $status = EntityStatus::from($request->input('status'));
        $perm = match ($status) {
            EntityStatus::Active => 'branch.activate',
            EntityStatus::Suspended => 'branch.suspend',
            EntityStatus::Archived => 'branch.archive',
        };
        abort_unless($request->user()->can($perm), 403);

        $this->org->setBranchStatus($branch, $status);

        return back()->with('success', "Branch {$status->value}.");
    }
}
