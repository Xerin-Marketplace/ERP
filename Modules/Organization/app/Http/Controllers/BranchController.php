<?php

namespace Modules\Organization\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\Organization\Enums\EntityStatus;
use Modules\Organization\Http\Requests\StoreBranchRequest;
use Modules\Organization\Http\Requests\UpdateBranchRequest;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\BranchAdministrator;
use Modules\Organization\Models\Company;
use Modules\Organization\Services\OrganizationService;
use Modules\Organization\Support\BranchScope;

class BranchController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private OrganizationService $org) {}

    public function index(Request $request)
    {
        $branches = BranchScope::apply(
            Branch::with('company:id,legal_name'), $request->user(), 'id'
        )
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $s = $request->input('search');
                $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%");
            }))
            ->when($request->filled('company'), fn ($q) => $q->where('company_id', $request->input('company')))
            ->when($request->filled('country'), fn ($q) => $q->where('country', $request->input('country')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy($request->input('sort', 'name'), $request->input('dir') === 'desc' ? 'desc' : 'asc')
            ->paginate(15)->withQueryString();

        return view('organization::branches.index', [
            'branches' => $branches,
            'companies' => Company::orderBy('legal_name')->get(['id', 'legal_name']),
            'countries' => Branch::whereNotNull('country')->distinct()->orderBy('country')->pluck('country'),
            'statuses' => EntityStatus::cases(),
        ]);
    }

    public function create()
    {
        return view('organization::branches.create', $this->formData());
    }

    public function store(StoreBranchRequest $request)
    {
        $branch = $this->org->createBranch($request->validated(), $request->user()->id);

        return redirect()->route('organization.branches.show', $branch)
            ->with('success', "Branch {$branch->name} created.");
    }

    public function show(Branch $branch)
    {
        $this->authorize('view', $branch);

        $branch->load([
            'company', 'departments.children', 'locations',
            'users:id,name,email,branch_id', 'creator',
            'administrators.user:id,name,email',
            'settings',
        ]);

        return view('organization::branches.show', [
            'branch' => $branch,
            'positions' => \Modules\Employee\Models\JobPosition::where('company_id', $branch->company_id)
                ->whereIn('department_id', $branch->departments->pluck('id')->push(null)->all())
                ->orderBy('title')->get(),
            'activity' => \App\Models\AuditLog::where('resource_type', Branch::class)
                ->where('resource_id', $branch->id)->latest()->limit(20)->get(),
            'candidates' => User::where('status', 'active')->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function edit(Branch $branch)
    {
        $this->authorize('update', $branch);

        return view('organization::branches.edit', [
            'branch' => $branch,
            ...$this->formData(),
        ]);
    }

    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        $this->authorize('update', $branch);
        $this->org->updateBranch($branch, $request->validated() + ['updated_by' => $request->user()->id]);

        return redirect()->route('organization.branches.show', $branch)
            ->with('success', 'Branch updated.');
    }

    public function activate(Branch $branch)
    {
        $this->authorize('activate', $branch);
        $this->org->activateBranch($branch);

        return back()->with('success', 'Branch activated.');
    }

    public function deactivate(Branch $branch)
    {
        $this->authorize('deactivate', $branch);
        $this->org->deactivateBranch($branch);

        return back()->with('success', 'Branch deactivated. Historical records are preserved.');
    }

    public function status(Request $request, Branch $branch)
    {
        $request->validate(['status' => ['required', Rule::enum(EntityStatus::class)]]);
        $status = EntityStatus::from($request->input('status'));

        match ($status) {
            EntityStatus::Active => $this->org->activateBranch($branch),
            EntityStatus::Suspended => $this->org->deactivateBranch($branch),
            EntityStatus::Archived => tap($branch, function () use ($branch) {
                abort_unless(request()->user()->can('organization.branches.manage'), 403);
                $this->org->setBranchStatus($branch, EntityStatus::Archived);
            }),
        };

        return back()->with('success', "Branch {$status->value}.");
    }

    public function assignAdministrator(Request $request, Branch $branch)
    {
        $this->authorize('manageAdministrators', $branch);
        $data = $request->validate(['user_id' => ['required', 'exists:users,id']]);
        $this->org->assignBranchAdministrator($branch, $data['user_id'], $request->user()->id);

        return back()->with('success', 'Branch administrator assigned.');
    }

    public function revokeAdministrator(Branch $branch, BranchAdministrator $administrator)
    {
        $this->authorize('manageAdministrators', $branch);
        abort_unless($administrator->branch_id === $branch->id, 404);
        $this->org->revokeBranchAdministrator($administrator, request()->user()->id);

        return back()->with('success', 'Branch administrator revoked.');
    }

    public function updateSettings(Request $request, Branch $branch)
    {
        $this->authorize('manageSettings', $branch);
        $data = $request->validate([
            'working_days' => ['nullable', 'array'],
            'working_days.*' => ['string', Rule::in(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'])],
            'business_open' => ['nullable', 'date_format:H:i'],
            'business_close' => ['nullable', 'date_format:H:i'],
        ]);

        foreach (['working_days' => 'json', 'business_open' => 'string', 'business_close' => 'string'] as $key => $type) {
            if (array_key_exists($key, $data)) {
                $this->org->setBranchSetting($branch, $key, $data[$key], $request->user()->id, $type);
            }
        }

        return back()->with('success', 'Branch settings updated.');
    }

    private function formData(): array
    {
        return [
            'companies' => Company::active()->orderBy('legal_name')->get(),
            'types' => Branch::TYPES,
            'timezones' => \DateTimeZone::listIdentifiers(),
            'currencies' => ['AED' => 'AED — UAE Dirham', 'TZS' => 'TZS — Tanzanian Shilling',
                             'USD' => 'USD — US Dollar', 'EUR' => 'EUR — Euro', 'KES' => 'KES — Kenyan Shilling',
                             'GBP' => 'GBP — British Pound'],
        ];
    }
}
