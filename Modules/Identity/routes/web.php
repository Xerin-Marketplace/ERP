<?php

use Illuminate\Support\Facades\Route;
use Modules\Identity\Http\Controllers\ActivationController;
use Modules\Identity\Http\Controllers\ProfileController;
use Modules\Identity\Http\Controllers\PublicRegistrationController;
use Modules\Identity\Http\Controllers\RegistrationAdminController;
use Modules\Identity\Http\Controllers\RoleManagementController;
use Modules\Identity\Http\Controllers\AccessControlController;
use Modules\Identity\Http\Controllers\UserManagementController;

/* --------------------------- Public portal ---------------------------- */

Route::middleware(['web', 'guest'])->group(function () {
    // Application tracking — public but rate-limited + email-bound
    Route::get('register/track', [PublicRegistrationController::class, 'track'])->name('register.track');
    Route::post('register/track', [PublicRegistrationController::class, 'trackLookup'])->name('register.track.lookup');

    // Account activation via secure invitation token
    Route::get('activate/{token}', [ActivationController::class, 'show'])->name('activate.show');
    Route::post('activate/{token}', [ActivationController::class, 'store'])->name('activate.store');
});

/* --------------------------- Authenticated ---------------------------- */

Route::middleware(['web', 'auth', 'verified', 'account.active'])
    ->prefix('identity')
    ->name('identity.')
    ->group(function () {

        Route::get('me', [ProfileController::class, 'show'])->name('me');

        /* IT registration workflow */
        Route::get('registrations', [RegistrationAdminController::class, 'index'])
            ->name('registrations.index')->middleware('permission:registration.view');
        Route::get('registrations/{application}', [RegistrationAdminController::class, 'show'])
            ->name('registrations.show')->middleware('permission:registration.view');
        Route::post('registrations/{application}/review', [RegistrationAdminController::class, 'startReview'])
            ->name('registrations.review')->middleware('permission:registration.review');
        Route::post('registrations/{application}/note', [RegistrationAdminController::class, 'addNote'])
            ->name('registrations.note')->middleware('permission:registration.review');
        Route::post('registrations/{application}/request-info', [RegistrationAdminController::class, 'requestInformation'])
            ->name('registrations.request-info')->middleware('permission:registration.request-info');
        Route::post('registrations/{application}/approve', [RegistrationAdminController::class, 'approve'])
            ->name('registrations.approve')->middleware('permission:registration.review'); // granular check inside
        Route::post('registrations/{application}/reject', [RegistrationAdminController::class, 'reject'])
            ->name('registrations.reject')->middleware('permission:registration.review');
        Route::post('registrations/{application}/provision', [RegistrationAdminController::class, 'provision'])
            ->name('registrations.provision')->middleware('permission:registration.review');
        Route::get('documents/{document}/download', [RegistrationAdminController::class, 'downloadDocument'])
            ->name('documents.download')->middleware('permission:registration.review');

        /* User management */
        Route::get('users', [UserManagementController::class, 'index'])
            ->name('users.index')->middleware('permission:users.view');
        Route::get('users/create', [UserManagementController::class, 'create'])
            ->name('users.create')->middleware('permission:users.create');
        Route::post('users', [UserManagementController::class, 'store'])
            ->name('users.store')->middleware('permission:users.create');
        Route::get('users/{user}', [UserManagementController::class, 'show'])
            ->name('users.show')->middleware('permission:users.view');
        Route::put('users/{user}', [UserManagementController::class, 'update'])
            ->name('users.update')->middleware('permission:users.update');
        Route::post('users/{user}/role', [UserManagementController::class, 'assignRole'])
            ->name('users.role')->middleware('permission:roles.assign');
        Route::post('users/{user}/suspend', [UserManagementController::class, 'suspend'])
            ->name('users.suspend')->middleware('permission:users.suspend');
        Route::post('users/{user}/activate', [UserManagementController::class, 'activate'])
            ->name('users.activate')->middleware('permission:users.activate');
        Route::post('users/{user}/deactivate', [UserManagementController::class, 'deactivate'])
            ->name('users.deactivate')->middleware('permission:users.deactivate');
        Route::post('users/{user}/revoke-sessions', [UserManagementController::class, 'revokeSessions'])
            ->name('users.revoke-sessions')->middleware('permission:users.sessions.revoke');
        Route::post('users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])
            ->name('users.reset-password')->middleware('permission:users.reset_password');
        Route::post('users/{user}/resend-invitation', [UserManagementController::class, 'resendInvitation'])
            ->name('users.resend-invitation')->middleware('permission:registration.provision');
        Route::post('users/{user}/assignments', [UserManagementController::class, 'reassign'])
            ->name('users.assignments')->middleware('permission:users.assignments.manage');

        /* Role management */
        Route::get('roles', [RoleManagementController::class, 'index'])
            ->name('roles.index')->middleware('permission:roles.view');
        Route::get('roles/create', [RoleManagementController::class, 'create'])
            ->name('roles.create')->middleware('permission:roles.create');
        Route::post('roles', [RoleManagementController::class, 'store'])
            ->name('roles.store')->middleware('permission:roles.create');
        Route::get('roles/{role}/edit', [RoleManagementController::class, 'edit'])
            ->name('roles.edit')->middleware('permission:roles.update');
        Route::put('roles/{role}', [RoleManagementController::class, 'update'])
            ->name('roles.update')->middleware('permission:roles.update');
        Route::post('roles/{role}/clone', [RoleManagementController::class, 'cloneRole'])
            ->name('roles.clone')->middleware('permission:roles.create');
        Route::delete('roles/{role}', [RoleManagementController::class, 'destroy'])
            ->name('roles.destroy')->middleware('permission:roles.delete');

        /* Access Control administration (Phase 2) */
        Route::get('invitations', [AccessControlController::class, 'invitations'])
            ->name('invitations.index')->middleware('permission:users.invite');
        Route::post('invitations/{invitation}/revoke', [AccessControlController::class, 'revokeInvitation'])
            ->name('invitations.revoke')->middleware('permission:users.invite');
        Route::get('permissions', [AccessControlController::class, 'permissions'])
            ->name('permissions.index')->middleware('permission:permissions.view');
        Route::get('permissions/matrix', [AccessControlController::class, 'matrix'])
            ->name('permissions.matrix')->middleware('permission:permissions.view');
        Route::put('permissions/matrix/{role}', [AccessControlController::class, 'updateMatrix'])
            ->name('permissions.matrix.update')->middleware('permission:permissions.manage');
        Route::get('access', [AccessControlController::class, 'access'])
            ->name('access.index')->middleware('permission:access.audit.view');
    });
