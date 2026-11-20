<?php

use Illuminate\Support\Facades\Route;
use Modules\Organization\Http\Controllers\Api\V1\OrganizationApiController as Api;
use Modules\Organization\Http\Controllers\Api\V1\BranchOperationsApiController as BranchOps;
use Modules\Organization\Http\Controllers\Api\V1\PositionApiController as PositionApi;

Route::middleware(['auth:sanctum', 'account.active'])
    ->prefix('v1/organization')
    ->name('organization.')
    ->group(function () {
        Route::get('overview', [Api::class, 'overview'])
            ->name('overview')->middleware('permission:organization.dashboard.view');
        Route::get('structure', [Api::class, 'structure'])
            ->name('structure')->middleware('permission:organization.structure.view');
        Route::get('audit-logs', [Api::class, 'auditLogs'])
            ->name('audit')->middleware('permission:organization.audit.view');

        Route::get('companies', [Api::class, 'companies'])
            ->name('companies.index')->middleware('permission:organization.company.view');
        Route::post('companies', [Api::class, 'storeCompany'])
            ->name('companies.store')->middleware('permission:organization.company.create');
        Route::get('companies/{company}', [Api::class, 'showCompany'])
            ->name('companies.show')->middleware('permission:organization.company.view');
        Route::put('companies/{company}', [Api::class, 'updateCompany'])
            ->name('companies.update')->middleware('permission:organization.company.update');

        Route::get('branches', [Api::class, 'branches'])
            ->name('branches.index')->middleware('permission:organization.branches.view');
        Route::post('branches', [Api::class, 'storeBranch'])
            ->name('branches.store')->middleware('permission:organization.branches.create');
        Route::get('branches/{branch}', [Api::class, 'showBranch'])
            ->name('branches.show')->middleware('permission:organization.branches.view');
        Route::put('branches/{branch}', [Api::class, 'updateBranch'])
            ->name('branches.update')->middleware('permission:organization.branches.update');
        Route::post('branches/{branch}/activate', [BranchOps::class, 'activate'])
            ->name('branches.activate')->middleware('permission:organization.branches.activate');
        Route::post('branches/{branch}/deactivate', [BranchOps::class, 'deactivate'])
            ->name('branches.deactivate')->middleware('permission:organization.branches.deactivate');
        Route::get('branches/{branch}/departments', [BranchOps::class, 'departments'])
            ->name('branches.departments')->middleware('permission:organization.branches.view');
        Route::get('branches/{branch}/administrators', [BranchOps::class, 'administrators'])
            ->name('branches.administrators')->middleware('permission:organization.branch_administrators.view');
        Route::post('branches/{branch}/administrators', [BranchOps::class, 'assignAdministrator'])
            ->name('branches.administrators.assign')->middleware('permission:organization.branch_administrators.assign');
        Route::delete('branches/{branch}/administrators/{administrator}', [BranchOps::class, 'revokeAdministrator'])
            ->name('branches.administrators.revoke')->middleware('permission:organization.branch_administrators.revoke');
        Route::get('branches/{branch}/settings', [BranchOps::class, 'settings'])
            ->name('branches.settings')->middleware('permission:organization.branches.view');
        Route::put('branches/{branch}/settings', [BranchOps::class, 'updateSettings'])
            ->name('branches.settings.update')->middleware('permission:organization.branches.manage');

        Route::get('positions', [PositionApi::class, 'index'])
            ->name('positions.index')->middleware('permission:organization.positions.view');
        Route::post('positions', [PositionApi::class, 'store'])
            ->name('positions.store')->middleware('permission:organization.positions.create');
        Route::get('positions/{position}', [PositionApi::class, 'show'])
            ->name('positions.show')->middleware('permission:organization.positions.view');
        Route::put('positions/{position}', [PositionApi::class, 'update'])
            ->name('positions.update')->middleware('permission:organization.positions.update');

        Route::get('locations', [Api::class, 'locations'])
            ->name('locations.index')->middleware('permission:organization.locations.view');
        Route::post('locations', [Api::class, 'storeLocation'])
            ->name('locations.store')->middleware('permission:organization.locations.create');

        Route::get('departments', [Api::class, 'departments'])
            ->name('departments.index')->middleware('permission:organization.departments.view');
        Route::post('departments', [Api::class, 'storeDepartment'])
            ->name('departments.store')->middleware('permission:organization.departments.create');
        Route::get('departments/{department}', [Api::class, 'showDepartment'])
            ->name('departments.show')->middleware('permission:organization.departments.view');
        Route::put('departments/{department}', [Api::class, 'updateDepartment'])
            ->name('departments.update')->middleware('permission:organization.departments.update');

        Route::post('{entity}/{id}/status', [Api::class, 'status'])
            ->whereIn('entity', ['companies', 'branches', 'departments'])
            ->name('status');
    });
