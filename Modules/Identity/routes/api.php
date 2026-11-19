<?php

use Illuminate\Support\Facades\Route;
use Modules\Identity\Http\Controllers\Api\V1\IdentityApiController as Api;

Route::prefix('v1/identity')->name('identity.')->group(function () {

    /* Public — throttled hard */
    Route::post('register', [Api::class, 'register'])
        ->name('register')->middleware('throttle:5,1');
    Route::get('registration/status/{reference}', [Api::class, 'registrationStatus'])
        ->name('registration.status');

    /* Authenticated */
    Route::middleware(['auth:sanctum', 'account.active'])->group(function () {
        Route::get('me', [Api::class, 'me'])->name('me');

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('registrations', [Api::class, 'adminRegistrations'])
                ->name('registrations.index')->middleware('permission:registration.view');
            Route::get('registrations/{application}', [Api::class, 'adminRegistrationShow'])
                ->name('registrations.show')->middleware('permission:registration.view');
            Route::post('registrations/{application}/review', [Api::class, 'review'])
                ->name('registrations.review')->middleware('permission:registration.review');
            Route::post('registrations/{application}/request-information', [Api::class, 'requestInformation'])
                ->name('registrations.request-info')->middleware('permission:registration.review');
            Route::post('registrations/{application}/approve', [Api::class, 'approve'])
                ->name('registrations.approve')->middleware('permission:registration.review');
            Route::post('registrations/{application}/reject', [Api::class, 'reject'])
                ->name('registrations.reject')->middleware('permission:registration.review');
            Route::post('registrations/{application}/provision', [Api::class, 'provision'])
                ->name('registrations.provision')->middleware('permission:registration.review');

            Route::get('users', [Api::class, 'users'])
                ->name('users.index')->middleware('permission:user.view');
            Route::get('users/{user}', [Api::class, 'userShow'])
                ->name('users.show')->middleware('permission:user.view');
            Route::post('users/{user}/suspend', [Api::class, 'suspendUser'])
                ->name('users.suspend')->middleware('permission:user.suspend');
            Route::post('users/{user}/activate', [Api::class, 'activateUser'])
                ->name('users.activate')->middleware('permission:user.activate');

            Route::get('roles', [Api::class, 'roles'])
                ->name('roles.index')->middleware('permission:role.view');
        });
    });
});
