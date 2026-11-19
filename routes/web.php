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

        return view('dashboard', compact('stats', 'activity'));
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // System administration — permission-protected
    Route::get('/admin/system', [SystemController::class, 'index'])
        ->middleware('permission:system.view')
        ->name('admin.system');
});

require __DIR__.'/auth.php';
