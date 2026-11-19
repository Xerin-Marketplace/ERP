<?php

use Illuminate\Support\Facades\Route;
use Modules\Organization\Http\Controllers\AuditController;
use Modules\Organization\Http\Controllers\BranchController;
use Modules\Organization\Http\Controllers\CompanyController;
use Modules\Organization\Http\Controllers\DepartmentController;
use Modules\Organization\Http\Controllers\LocationController;
use Modules\Organization\Http\Controllers\OverviewController;
use Modules\Organization\Http\Controllers\SettingsController;
use Modules\Organization\Http\Controllers\StructureController;

Route::middleware(['web', 'auth', 'verified', 'account.active'])
    ->prefix('organization')
    ->name('organization.')
    ->group(function () {
        Route::get('/', [OverviewController::class, 'index'])
            ->name('index')->middleware('permission:organization.view');

        Route::get('structure', [StructureController::class, 'index'])
            ->name('structure')->middleware('permission:organization.structure.view');

        Route::get('audit', [AuditController::class, 'index'])
            ->name('audit')->middleware('permission:organization.audit.view');

        Route::get('settings', [SettingsController::class, 'edit'])
            ->name('settings')->middleware('permission:organization.manage');
        Route::put('settings', [SettingsController::class, 'update'])
            ->name('settings.update')->middleware('permission:organization.manage');

        /* Companies */
        Route::get('companies', [CompanyController::class, 'index'])
            ->name('companies.index')->middleware('permission:company.view');
        Route::get('companies/create', [CompanyController::class, 'create'])
            ->name('companies.create')->middleware('permission:company.create');
        Route::post('companies', [CompanyController::class, 'store'])
            ->name('companies.store')->middleware('permission:company.create');
        Route::get('companies/{company}', [CompanyController::class, 'show'])
            ->name('companies.show')->middleware('permission:company.view');
        Route::get('companies/{company}/edit', [CompanyController::class, 'edit'])
            ->name('companies.edit')->middleware('permission:company.update');
        Route::put('companies/{company}', [CompanyController::class, 'update'])
            ->name('companies.update')->middleware('permission:company.update');
        Route::post('companies/{company}/status', [CompanyController::class, 'status'])
            ->name('companies.status')->middleware('permission:company.update');

        /* Branches */
        Route::get('branches', [BranchController::class, 'index'])
            ->name('branches.index')->middleware('permission:branch.view');
        Route::get('branches/create', [BranchController::class, 'create'])
            ->name('branches.create')->middleware('permission:branch.create');
        Route::post('branches', [BranchController::class, 'store'])
            ->name('branches.store')->middleware('permission:branch.create');
        Route::get('branches/{branch}', [BranchController::class, 'show'])
            ->name('branches.show')->middleware('permission:branch.view');
        Route::get('branches/{branch}/edit', [BranchController::class, 'edit'])
            ->name('branches.edit')->middleware('permission:branch.update');
        Route::put('branches/{branch}', [BranchController::class, 'update'])
            ->name('branches.update')->middleware('permission:branch.update');
        Route::post('branches/{branch}/status', [BranchController::class, 'status'])
            ->name('branches.status')->middleware('permission:branch.update');

        /* Locations */
        Route::get('locations', [LocationController::class, 'index'])
            ->name('locations.index')->middleware('permission:location.view');
        Route::get('locations/create', [LocationController::class, 'create'])
            ->name('locations.create')->middleware('permission:location.create');
        Route::post('locations', [LocationController::class, 'store'])
            ->name('locations.store')->middleware('permission:location.create');
        Route::post('locations/{location}/status', [LocationController::class, 'status'])
            ->name('locations.status')->middleware('permission:location.update');

        /* Departments */
        Route::get('departments', [DepartmentController::class, 'index'])
            ->name('departments.index')->middleware('permission:department.view');
        Route::get('departments/create', [DepartmentController::class, 'create'])
            ->name('departments.create')->middleware('permission:department.create');
        Route::post('departments', [DepartmentController::class, 'store'])
            ->name('departments.store')->middleware('permission:department.create');
        Route::get('departments/{department}', [DepartmentController::class, 'show'])
            ->name('departments.show')->middleware('permission:department.view');
        Route::get('departments/{department}/edit', [DepartmentController::class, 'edit'])
            ->name('departments.edit')->middleware('permission:department.update');
        Route::put('departments/{department}', [DepartmentController::class, 'update'])
            ->name('departments.update')->middleware('permission:department.update');
        Route::post('departments/{department}/status', [DepartmentController::class, 'status'])
            ->name('departments.status')->middleware('permission:department.update');
    });
