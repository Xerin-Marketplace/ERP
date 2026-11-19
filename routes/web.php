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
        return view('dashboard');
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
