<?php

use Illuminate\Support\Facades\Route;
use Modules\Organization\Http\Controllers\Api\V1\OrganizationApiController as Api;

Route::middleware(['auth:sanctum', 'account.active'])
    ->prefix('v1/organization')
    ->name('organization.')
    ->group(function () {
        Route::get('overview', [Api::class, 'overview'])
            ->name('overview')->middleware('permission:organization.view');
        Route::get('structure', [Api::class, 'structure'])
            ->name('structure')->middleware('permission:organization.structure.view');
        Route::get('audit-logs', [Api::class, 'auditLogs'])
            ->name('audit')->middleware('permission:organization.audit.view');

        Route::get('companies', [Api::class, 'companies'])
            ->name('companies.index')->middleware('permission:company.view');
        Route::post('companies', [Api::class, 'storeCompany'])
            ->name('companies.store')->middleware('permission:company.create');
        Route::get('companies/{company}', [Api::class, 'showCompany'])
            ->name('companies.show')->middleware('permission:company.view');
        Route::put('companies/{company}', [Api::class, 'updateCompany'])
            ->name('companies.update')->middleware('permission:company.update');

        Route::get('branches', [Api::class, 'branches'])
            ->name('branches.index')->middleware('permission:branch.view');
        Route::post('branches', [Api::class, 'storeBranch'])
            ->name('branches.store')->middleware('permission:branch.create');
        Route::get('branches/{branch}', [Api::class, 'showBranch'])
            ->name('branches.show')->middleware('permission:branch.view');
        Route::put('branches/{branch}', [Api::class, 'updateBranch'])
            ->name('branches.update')->middleware('permission:branch.update');

        Route::get('locations', [Api::class, 'locations'])
            ->name('locations.index')->middleware('permission:location.view');
        Route::post('locations', [Api::class, 'storeLocation'])
            ->name('locations.store')->middleware('permission:location.create');

        Route::get('departments', [Api::class, 'departments'])
            ->name('departments.index')->middleware('permission:department.view');
        Route::post('departments', [Api::class, 'storeDepartment'])
            ->name('departments.store')->middleware('permission:department.create');
        Route::get('departments/{department}', [Api::class, 'showDepartment'])
            ->name('departments.show')->middleware('permission:department.view');
        Route::put('departments/{department}', [Api::class, 'updateDepartment'])
            ->name('departments.update')->middleware('permission:department.update');

        Route::post('{entity}/{id}/status', [Api::class, 'status'])
            ->whereIn('entity', ['companies', 'branches', 'departments'])
            ->name('status');
    });
