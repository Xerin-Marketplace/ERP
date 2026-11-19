<?php

namespace Modules\Organization\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Organization\Enums\EntityStatus;
use Modules\Organization\Http\Requests\StoreCompanyRequest;
use Modules\Organization\Http\Requests\UpdateCompanyRequest;
use Modules\Organization\Models\Company;
use Modules\Organization\Services\OrganizationService;

class CompanyController extends Controller
{
    public function __construct(private OrganizationService $org) {}

    public function index(Request $request)
    {
        $companies = Company::with('parent:id,legal_name')
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $s = $request->input('search');
                $q->where('legal_name', 'like', "%{$s}%")
                  ->orWhere('trading_name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%");
            }))
            ->when($request->filled('type'), fn ($q) => $q->where('company_type', $request->input('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy($request->input('sort', 'legal_name'), $request->input('dir') === 'desc' ? 'desc' : 'asc')
            ->paginate(15)->withQueryString();

        return view('organization::companies.index', [
            'companies' => $companies,
            'types' => Company::TYPES,
            'statuses' => EntityStatus::cases(),
        ]);
    }

    public function create()
    {
        return view('organization::companies.create', [
            'types' => Company::TYPES,
            'parents' => Company::active()->orderBy('legal_name')->get(),
        ]);
    }

    public function store(StoreCompanyRequest $request)
    {
        $company = $this->org->createCompany($request->validated(), $request->user()->id);

        return redirect()->route('organization.companies.show', $company)
            ->with('success', "Company {$company->legal_name} created.");
    }

    public function show(Company $company)
    {
        $company->load(['parent', 'subsidiaries', 'branches.departments', 'departments', 'locations', 'creator']);

        return view('organization::companies.show', compact('company'));
    }

    public function edit(Company $company)
    {
        return view('organization::companies.edit', [
            'company' => $company,
            'types' => Company::TYPES,
            'parents' => Company::active()->where('id', '!=', $company->id)->orderBy('legal_name')->get(),
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company)
    {
        $this->org->updateCompany($company, $request->validated());

        return redirect()->route('organization.companies.show', $company)
            ->with('success', 'Company updated.');
    }

    public function status(Request $request, Company $company)
    {
        $request->validate(['status' => ['required', \Illuminate\Validation\Rule::enum(EntityStatus::class)]]);
        $status = EntityStatus::from($request->input('status'));

        $action = match ($status) {
            EntityStatus::Active => 'activate',
            EntityStatus::Suspended => 'suspend',
            EntityStatus::Archived => 'archive',
        };
        abort_unless($request->user()->can("company.{$action}"), 403);

        $this->org->setCompanyStatus($company, $status);

        return back()->with('success', "Company {$status->value}.");
    }
}
