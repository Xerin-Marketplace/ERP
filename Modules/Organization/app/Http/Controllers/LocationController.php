<?php

namespace Modules\Organization\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Organization\Enums\EntityStatus;
use Modules\Organization\Http\Requests\StoreLocationRequest;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Location;
use Modules\Organization\Services\OrganizationService;

class LocationController extends Controller
{
    public function __construct(private OrganizationService $org) {}

    public function index(Request $request)
    {
        $locations = Location::with('company:id,legal_name', 'branch:id,name')
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $s = $request->input('search');
                $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%");
            }))
            ->when($request->filled('type'), fn ($q) => $q->where('location_type', $request->input('type')))
            ->when($request->filled('company'), fn ($q) => $q->where('company_id', $request->input('company')))
            ->orderBy('name')
            ->paginate(15)->withQueryString();

        return view('organization::locations.index', [
            'locations' => $locations,
            'types' => Location::TYPES,
            'companies' => Company::orderBy('legal_name')->get(['id', 'legal_name']),
        ]);
    }

    public function create()
    {
        return view('organization::locations.create', [
            'companies' => Company::active()->orderBy('legal_name')->get(),
            'branches' => Branch::active()->orderBy('name')->get(),
            'types' => Location::TYPES,
        ]);
    }

    public function store(StoreLocationRequest $request)
    {
        $location = $this->org->createLocation($request->validated(), $request->user()->id);

        return redirect()->route('organization.locations.index')
            ->with('success', "Location {$location->name} created.");
    }

    public function status(Request $request, Location $location)
    {
        abort_unless($request->user()->can('organization.locations.update'), 403);
        $request->validate(['status' => ['required', \Illuminate\Validation\Rule::enum(EntityStatus::class)]]);
        $this->org->setLocationStatus($location, EntityStatus::from($request->input('status')));

        return back()->with('success', 'Location updated.');
    }
}
