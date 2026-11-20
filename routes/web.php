<?php

use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

// API documentation (OpenAPI via Redoc)
Route::get('/api-docs', fn () => view('api-docs'))->name('api.docs');
Route::get('/api-docs/openapi.yaml', fn () => response(
    file_get_contents(base_path('docs/api/openapi.yaml')), 200,
    ['Content-Type' => 'text/yaml; charset=utf-8']
))->name('api.docs.spec');

Route::middleware(['auth', 'verified', 'account.active'])->group(function () {

    Route::get('/onboarding', function () {
        $u = auth()->user();
        $steps = [
            ['label' => 'Account activated', 'done' => ($u->status->value ?? $u->status) === 'active'],
            ['label' => 'Email verified', 'done' => (bool) $u->email_verified_at],
            ['label' => 'Password set', 'done' => ! $u->must_change_password],
            ['label' => 'Phone number added', 'done' => (bool) $u->phone],
            ['label' => 'Department assigned', 'done' => $u->assignments()->current()->exists()],
        ];
        return view('onboarding', ['steps' => $steps,
            'complete' => ! in_array(false, array_column($steps, 'done'), true)]);
    })->name('onboarding');

    // MFA — enrollment + session challenge for privileged roles (spec §16.4).
    Route::get('/mfa/enroll', [\App\Http\Controllers\MfaChallengeController::class, 'enroll'])->name('mfa.enroll');
    Route::post('/mfa/enroll', [\App\Http\Controllers\MfaChallengeController::class, 'confirmEnroll'])->name('mfa.enroll.confirm');
    Route::get('/mfa/challenge', [\App\Http\Controllers\MfaChallengeController::class, 'challenge'])->name('mfa.challenge');
    Route::post('/mfa/challenge', [\App\Http\Controllers\MfaChallengeController::class, 'verify'])->name('mfa.verify');

    Route::get('/dashboard', function () {
        $stats = [
            'companies' => \Modules\Organization\Models\Company::count(),
            'active_companies' => \Modules\Organization\Models\Company::where('status', 'active')->count(),
            'branches' => \Modules\Organization\Models\Branch::count(),
            'active_branches' => \Modules\Organization\Models\Branch::where('status', 'active')->count(),
            'suspended_branches' => \Modules\Organization\Models\Branch::where('status', 'suspended')->count(),
            'departments' => \Modules\Organization\Models\Department::count(),
            'active_departments' => \Modules\Organization\Models\Department::where('status', 'active')->count(),
            'users' => \App\Models\User::count(),
            'branches_by_status' => \Modules\Organization\Models\Branch::selectRaw('status, COUNT(*) as n')
                ->groupBy('status')->pluck('n', 'status')->all(),
            'departments_by_company' => \Modules\Organization\Models\Department::join('companies', 'departments.company_id', '=', 'companies.id')
                ->selectRaw('companies.trading_name as name, COUNT(*) as n')
                ->groupBy('companies.trading_name')->pluck('n', 'name')->all(),
        ];
        $activity = \App\Models\AuditLog::with('user:id,name')->latest()->limit(8)->get();

        // Personalized blocks — IT staff see the registration queue; every
        // provisioned user sees their organizational placement.
        $user = auth()->user();
        $identity = [
            'registrations' => $user->can('registration.view')
                ? app(\Modules\Identity\Services\RegistrationService::class)->dashboardStats()
                : null,
            'pending_apps' => $user->can('registration.view')
                ? \Modules\Identity\Models\RegistrationApplication::whereIn('status', ['submitted', 'pending_review', 'under_review'])->latest()->limit(5)->get()
                : collect(),
            'my_assignment' => $user->load('currentAssignment.company:id,legal_name', 'currentAssignment.branch:id,name', 'currentAssignment.department:id,name')->currentAssignment,
        ];

        return view('dashboard', compact('stats', 'activity', 'identity'));
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Language preference (EN/SW foundation)
    Route::post('/locale', function (\Illuminate\Http\Request $request) {
        $locale = $request->validate(['locale' => 'required|string|in:en,sw,fr'])['locale'];
        $request->session()->put('locale', $locale);
        $request->user()->profile()?->update(['preferred_language' => $locale]);
        return back();
    })->name('locale.set');

    // Executive Command Center — group-wide BI for CEO/Super Admin.
    Route::prefix('executive')->name('executive.')->middleware('permission:executive.command.view')->group(function () {
        Route::get('/', [\App\Http\Controllers\ExecutiveController::class, 'dashboard'])->name('dashboard');
        Route::get('financial', [\App\Http\Controllers\ExecutiveController::class, 'financial'])->name('financial');
        Route::get('branches', [\App\Http\Controllers\ExecutiveController::class, 'branches'])->name('branches');
        Route::get('branches/{branch}', [\App\Http\Controllers\ExecutiveController::class, 'branch'])->name('branch');
        Route::get('departments', [\App\Http\Controllers\ExecutiveController::class, 'departments'])->name('departments');
        Route::get('sales', [\App\Http\Controllers\ExecutiveController::class, 'sales'])->name('sales');
        Route::get('employees', [\App\Http\Controllers\ExecutiveController::class, 'employees'])->name('employees');
        Route::get('tasks', [\App\Http\Controllers\ExecutiveController::class, 'tasks'])->name('tasks');
        Route::get('tenders', [\App\Http\Controllers\ExecutiveController::class, 'tenders'])->name('tenders');
        Route::get('activity', [\App\Http\Controllers\ExecutiveController::class, 'activity'])->name('activity');
    });

    // System administration — permission-protected
    Route::get('/admin/system', [SystemController::class, 'index'])
        ->middleware('permission:system.view')
        ->name('admin.system');
    // Legacy URL — the SystemAdministration module owns settings now.
    Route::redirect('/admin/settings', '/system-admin/settings/General', 301)->name('admin.settings');
});

require __DIR__.'/auth.php';
