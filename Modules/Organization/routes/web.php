<?php

use Illuminate\Support\Facades\Route;
use Modules\Organization\Http\Controllers\AuditController;
use Modules\Organization\Http\Controllers\BranchController;
use Modules\Organization\Http\Controllers\CompanyController;
use Modules\Organization\Http\Controllers\DepartmentController;
use Modules\Organization\Http\Controllers\LocationController;
use Modules\Organization\Http\Controllers\PositionController;
use Modules\Organization\Http\Controllers\OverviewController;
use Modules\Organization\Http\Controllers\SettingsController;
use Modules\Organization\Http\Controllers\StructureController;

Route::middleware(['web', 'auth', 'verified', 'account.active'])
    ->prefix('organization')
    ->name('organization.')
    ->group(function () {
        Route::get('/', [OverviewController::class, 'index'])
            ->name('index')->middleware('permission:organization.dashboard.view');

        Route::get('structure', [StructureController::class, 'index'])
            ->name('structure')->middleware('permission:organization.structure.view');

        Route::get('audit', [AuditController::class, 'index'])
            ->name('audit')->middleware('permission:organization.audit.view');

        Route::get('settings', [SettingsController::class, 'edit'])
            ->name('settings')->middleware('permission:organization.settings.manage');
        Route::put('settings', [SettingsController::class, 'update'])
            ->name('settings.update')->middleware('permission:organization.settings.manage');

        /* Companies */
        Route::get('companies', [CompanyController::class, 'index'])
            ->name('companies.index')->middleware('permission:organization.company.view');
        Route::get('companies/create', [CompanyController::class, 'create'])
            ->name('companies.create')->middleware('permission:organization.company.create');
        Route::post('companies', [CompanyController::class, 'store'])
            ->name('companies.store')->middleware('permission:organization.company.create');
        Route::get('companies/{company}', [CompanyController::class, 'show'])
            ->name('companies.show')->middleware('permission:organization.company.view');
        Route::get('companies/{company}/edit', [CompanyController::class, 'edit'])
            ->name('companies.edit')->middleware('permission:organization.company.update');
        Route::put('companies/{company}', [CompanyController::class, 'update'])
            ->name('companies.update')->middleware('permission:organization.company.update');
        Route::post('companies/{company}/status', [CompanyController::class, 'status'])
            ->name('companies.status')->middleware('permission:organization.company.update');

        /* Branches */
        Route::get('branches', [BranchController::class, 'index'])
            ->name('branches.index')->middleware('permission:organization.branches.view');
        Route::get('branches/create', [BranchController::class, 'create'])
            ->name('branches.create')->middleware('permission:organization.branches.create');
        Route::post('branches', [BranchController::class, 'store'])
            ->name('branches.store')->middleware('permission:organization.branches.create');
        Route::get('branches/{branch}', [BranchController::class, 'show'])
            ->name('branches.show')->middleware('permission:organization.branches.view');
        Route::get('branches/{branch}/edit', [BranchController::class, 'edit'])
            ->name('branches.edit')->middleware('permission:organization.branches.update');
        Route::put('branches/{branch}', [BranchController::class, 'update'])
            ->name('branches.update')->middleware('permission:organization.branches.update');
        Route::post('branches/{branch}/activate', [BranchController::class, 'activate'])
            ->name('branches.activate')->middleware('permission:organization.branches.activate');
        Route::post('branches/{branch}/deactivate', [BranchController::class, 'deactivate'])
            ->name('branches.deactivate')->middleware('permission:organization.branches.deactivate');
        Route::post('branches/{branch}/status', [BranchController::class, 'status'])
            ->name('branches.status')->middleware('permission:organization.branches.update');
        Route::post('branches/{branch}/administrators', [BranchController::class, 'assignAdministrator'])
            ->name('branches.administrators.assign')->middleware('permission:organization.branch_administrators.assign');
        Route::delete('branches/{branch}/administrators/{administrator}', [BranchController::class, 'revokeAdministrator'])
            ->name('branches.administrators.revoke')->middleware('permission:organization.branch_administrators.revoke');
        Route::put('branches/{branch}/settings', [BranchController::class, 'updateSettings'])
            ->name('branches.settings.update')->middleware('permission:organization.branches.manage');

        /* Positions (job_positions — shared with Employee module) */
        Route::get('positions', [PositionController::class, 'index'])
            ->name('positions.index')->middleware('permission:organization.positions.view');
        Route::get('positions/create', [PositionController::class, 'create'])
            ->name('positions.create')->middleware('permission:organization.positions.create');
        Route::post('positions', [PositionController::class, 'store'])
            ->name('positions.store')->middleware('permission:organization.positions.create');
        Route::get('positions/{position}/edit', [PositionController::class, 'edit'])
            ->name('positions.edit')->middleware('permission:organization.positions.update');
        Route::put('positions/{position}', [PositionController::class, 'update'])
            ->name('positions.update')->middleware('permission:organization.positions.update');
        Route::post('positions/{position}/status', [PositionController::class, 'status'])
            ->name('positions.status')->middleware('permission:organization.positions.manage');

        /* Locations */
        Route::get('locations', [LocationController::class, 'index'])
            ->name('locations.index')->middleware('permission:organization.locations.view');
        Route::get('locations/create', [LocationController::class, 'create'])
            ->name('locations.create')->middleware('permission:organization.locations.create');
        Route::post('locations', [LocationController::class, 'store'])
            ->name('locations.store')->middleware('permission:organization.locations.create');
        Route::post('locations/{location}/status', [LocationController::class, 'status'])
            ->name('locations.status')->middleware('permission:organization.locations.update');

        /* Departments */
        Route::get('departments', [DepartmentController::class, 'index'])
            ->name('departments.index')->middleware('permission:organization.departments.view');
        Route::get('departments/create', [DepartmentController::class, 'create'])
            ->name('departments.create')->middleware('permission:organization.departments.create');
        Route::post('departments', [DepartmentController::class, 'store'])
            ->name('departments.store')->middleware('permission:organization.departments.create');
        Route::get('departments/{department}', [DepartmentController::class, 'show'])
            ->name('departments.show')->middleware('permission:organization.departments.view');
        Route::get('departments/{department}/edit', [DepartmentController::class, 'edit'])
            ->name('departments.edit')->middleware('permission:organization.departments.update');
        Route::put('departments/{department}', [DepartmentController::class, 'update'])
            ->name('departments.update')->middleware('permission:organization.departments.update');
        Route::post('departments/{department}/status', [DepartmentController::class, 'status'])
            ->name('departments.status')->middleware('permission:organization.departments.update');
    });
