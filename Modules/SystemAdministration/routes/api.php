<?php

use Illuminate\Support\Facades\Route;
use Modules\SystemAdministration\Http\Controllers\Api;

// JSON control-plane API — same permission model as the web UI.
Route::middleware(['api', 'auth:sanctum', 'account.active'])
    ->prefix('v1/system-administration')
    ->name('systemadmin.')
    ->group(function () {
        Route::get('dashboard', [Api\DashboardApiController::class, 'show'])
            ->middleware('permission:sysadmin.dashboard.view')->name('dashboard');
        Route::get('settings', [Api\SettingsApiController::class, 'index'])
            ->middleware('permission:sysadmin.settings.manage')->name('settings.index');
        Route::put('settings', [Api\SettingsApiController::class, 'update'])
            ->middleware('permission:sysadmin.settings.manage')->name('settings.update');
        Route::get('feature-flags/{key}/check', [Api\FeatureFlagApiController::class, 'check'])->name('flags.check');
        Route::get('health', [Api\DashboardApiController::class, 'health'])
            ->middleware('permission:sysadmin.monitoring.view')->name('health');
    });
