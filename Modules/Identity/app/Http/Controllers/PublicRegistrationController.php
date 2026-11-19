<?php

namespace Modules\Identity\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Identity\Http\Requests\SubmitRegistrationRequest;
use Modules\Identity\Services\RegistrationService;
use Modules\Organization\Enums\EntityStatus;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;

class PublicRegistrationController extends Controller
{
    public function __construct(private RegistrationService $registrations) {}

    public function create()
    {
        return view('identity::public.register', [
            'companies' => Company::where('status', EntityStatus::Active)->orderBy('legal_name')->get(['id', 'legal_name', 'trading_name']),
            'branches' => Branch::where('status', EntityStatus::Active)->with('company:id,legal_name')->orderBy('name')->get(['id', 'name', 'company_id']),
            'departments' => Department::where('status', EntityStatus::Active)->orderBy('name')->get(['id', 'name', 'company_id', 'branch_id']),
        ]);
    }

    public function store(SubmitRegistrationRequest $request)
    {
        $data = $request->validated();
        $data['email'] = strtolower($data['email']);

        $documents = [];
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('registration-photos', 'local');
        }
        if ($request->hasFile('employment_id_document')) {
            $documents['employment_id'] = $request->file('employment_id_document');
        }
        if ($request->hasFile('supporting_document')) {
            $documents['supporting'] = $request->file('supporting_document');
        }

        $application = $this->registrations->submit($data, $documents);

        return redirect()->route('register.track')
            ->with('success', 'Application received.')
            ->with('reference', $application->reference);
    }

    /* ----------------------- Application tracking ----------------------- */

    public function track()
    {
        return view('identity::public.track', [
            'reference' => session('reference') ?? request('reference'),
        ]);
    }

    public function trackLookup(Request $request)
    {
        $request->validate([
            'reference' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email'],
        ]);

        // Anti-enumeration: hard rate limit per IP
        $key = 'reg-track:'.strtolower($request->ip());
        if (RateLimiter::tooManyAttempts($key, 8)) {
            return back()->withErrors(['reference' => 'Too many attempts. Please try again later.']);
        }
        RateLimiter::hit($key, 300);

        $application = $this->registrations->lookupForApplicant(
            $request->input('reference'), $request->input('email')
        );

        if (! $application) {
            return back()->withErrors(['reference' => 'No application found for the given reference and email.'])
                ->withInput();
        }

        return view('identity::public.track-result', [
            'application' => $application,
            'timeline' => $this->registrations->publicTimeline($application),
        ]);
    }
}
