<?php

use Illuminate\Support\Facades\Route;
use Modules\SystemAdministration\Http\Controllers;

// Super Admin & System Control Center — each area carries its own permission.
Route::middleware(['web', 'auth', 'verified', 'account.active'])
    ->prefix('system-admin')
    ->name('systemadmin.')
    ->group(function () {

        Route::get('/', [Controllers\ControlCenterController::class, 'index'])
            ->name('dashboard')->middleware('permission:sysadmin.dashboard.view');

        // Branding
        Route::get('branding', [Controllers\BrandingController::class, 'edit'])
            ->name('branding')->middleware('permission:sysadmin.branding.manage');
        Route::put('branding', [Controllers\BrandingController::class, 'update'])
            ->name('branding.update')->middleware('permission:sysadmin.branding.manage');
        Route::post('branding/assets', [Controllers\BrandingController::class, 'uploadLogo'])
            ->name('branding.assets.upload')->middleware('permission:sysadmin.branding.manage');
        Route::post('branding/assets/{asset}/restore', [Controllers\BrandingController::class, 'restoreLogo'])
            ->name('branding.assets.restore')->middleware('permission:sysadmin.branding.manage');

        // General / Regional / Display settings
        Route::get('settings/{group?}', [Controllers\SettingsController::class, 'edit'])
            ->name('settings')->middleware('permission:sysadmin.settings.manage')
            ->whereIn('group', ['General', 'Regional', 'Display']);
        Route::put('settings/{group}', [Controllers\SettingsController::class, 'update'])
            ->name('settings.update')->middleware('permission:sysadmin.settings.manage')
            ->whereIn('group', ['General', 'Regional', 'Display']);

        // System rules & policies
        Route::get('rules', [Controllers\RulesController::class, 'index'])
            ->name('rules')->middleware('permission:sysadmin.rules.manage');
        Route::put('rules', [Controllers\RulesController::class, 'update'])
            ->name('rules.update')->middleware('permission:sysadmin.rules.manage');

        // Module registry
        Route::get('modules', [Controllers\ModuleManagerController::class, 'index'])
            ->name('modules')->middleware('permission:sysadmin.modules.manage');
        Route::post('modules/{name}/toggle', [Controllers\ModuleManagerController::class, 'toggle'])
            ->name('modules.toggle')->middleware('permission:sysadmin.modules.manage');

        // Navigation management
        Route::get('navigation', [Controllers\NavigationController::class, 'index'])
            ->name('navigation')->middleware('permission:sysadmin.navigation.manage');
        Route::put('navigation/{key}', [Controllers\NavigationController::class, 'update'])
            ->name('navigation.update')->middleware('permission:sysadmin.navigation.manage')
            ->where('key', '.+');
        Route::post('navigation/reset', [Controllers\NavigationController::class, 'reset'])
            ->name('navigation.reset')->middleware('permission:sysadmin.navigation.manage');

        /* Command Center */
        Route::get('command-center', [Controllers\CommandCenterController::class, 'index'])
            ->name('commands.index')->middleware('permission:sysadmin.commands.view');
        Route::post('command-center/{id}/execute', [Controllers\CommandCenterController::class, 'execute'])
            ->name('commands.execute')->middleware('permission:sysadmin.commands.execute');
        Route::get('command-history', [Controllers\CommandCenterController::class, 'history'])
            ->name('commands.history')->middleware('permission:sysadmin.commands.view');
        Route::post('command-executions/{execution}/approve', [Controllers\CommandCenterController::class, 'approve'])
            ->name('commands.approve')->middleware('permission:sysadmin.commands.approve');
        Route::post('command-executions/{execution}/reject', [Controllers\CommandCenterController::class, 'reject'])
            ->name('commands.reject')->middleware('permission:sysadmin.commands.approve');

        // Feature flags
        Route::get('feature-flags', [Controllers\FeatureFlagController::class, 'index'])
            ->name('flags')->middleware('permission:sysadmin.flags.manage');
        Route::post('feature-flags', [Controllers\FeatureFlagController::class, 'store'])
            ->name('flags.store')->middleware('permission:sysadmin.flags.manage');
        Route::put('feature-flags/{flag}', [Controllers\FeatureFlagController::class, 'update'])
            ->name('flags.update')->middleware('permission:sysadmin.flags.manage');
        Route::delete('feature-flags/{flag}', [Controllers\FeatureFlagController::class, 'destroy'])
            ->name('flags.destroy')->middleware('permission:sysadmin.flags.manage');

        // Announcements
        Route::get('announcements', [Controllers\AnnouncementController::class, 'index'])
            ->name('announcements')->middleware('permission:sysadmin.announcements.manage');
        Route::post('announcements', [Controllers\AnnouncementController::class, 'store'])
            ->name('announcements.store')->middleware('permission:sysadmin.announcements.manage');
        Route::put('announcements/{announcement}', [Controllers\AnnouncementController::class, 'update'])
            ->name('announcements.update')->middleware('permission:sysadmin.announcements.manage');
        Route::delete('announcements/{announcement}', [Controllers\AnnouncementController::class, 'destroy'])
            ->name('announcements.destroy')->middleware('permission:sysadmin.announcements.manage');

        // Integrations
        Route::get('integrations', [Controllers\IntegrationController::class, 'index'])
            ->name('integrations')->middleware('permission:sysadmin.integrations.manage');
        Route::post('integrations', [Controllers\IntegrationController::class, 'store'])
            ->name('integrations.store')->middleware('permission:sysadmin.integrations.manage');
        Route::put('integrations/{integration}', [Controllers\IntegrationController::class, 'update'])
            ->name('integrations.update')->middleware('permission:sysadmin.integrations.manage');
        Route::post('integrations/{integration}/toggle', [Controllers\IntegrationController::class, 'toggle'])
            ->name('integrations.toggle')->middleware('permission:sysadmin.integrations.manage');
        Route::post('integrations/{integration}/test', [Controllers\IntegrationController::class, 'test'])
            ->name('integrations.test')->middleware('permission:sysadmin.integrations.manage');
        Route::delete('integrations/{integration}', [Controllers\IntegrationController::class, 'destroy'])
            ->name('integrations.destroy')->middleware('permission:sysadmin.integrations.manage');

        // Security center
        Route::get('security', [Controllers\SecurityController::class, 'index'])
            ->name('security')->middleware('permission:sysadmin.security.manage');
        Route::post('security/sessions/revoke', [Controllers\SecurityController::class, 'revokeSession'])
            ->name('security.sessions.revoke')->middleware('permission:sysadmin.security.manage');
        Route::post('security/users/{user}/revoke-sessions', [Controllers\SecurityController::class, 'revokeUserSessions'])
            ->name('security.users.revoke')->middleware('permission:sysadmin.security.manage');

        // Audit center
        Route::get('audit', [Controllers\AuditCenterController::class, 'index'])
            ->name('audit')->middleware('permission:sysadmin.audit.view');
        Route::get('audit/export', [Controllers\AuditCenterController::class, 'export'])
            ->name('audit.export')->middleware('permission:sysadmin.audit.view');

        // Maintenance
        Route::get('maintenance', [Controllers\MaintenanceController::class, 'index'])
            ->name('maintenance')->middleware('permission:sysadmin.maintenance.manage');
        Route::post('maintenance/read-only', [Controllers\MaintenanceController::class, 'toggleReadOnly'])
            ->name('maintenance.readonly')->middleware('permission:sysadmin.maintenance.manage');
        Route::post('maintenance/down', [Controllers\MaintenanceController::class, 'down'])
            ->name('maintenance.down')->middleware('permission:sysadmin.maintenance.manage');
        Route::post('maintenance/up', [Controllers\MaintenanceController::class, 'up'])
            ->name('maintenance.up')->middleware('permission:sysadmin.maintenance.manage');

        // Monitoring
        Route::get('monitoring', [Controllers\MonitoringController::class, 'index'])
            ->name('monitoring')->middleware('permission:sysadmin.monitoring.view');
        Route::post('monitoring/jobs/{id}/retry', [Controllers\MonitoringController::class, 'retryJob'])
            ->name('monitoring.jobs.retry')->middleware('permission:sysadmin.monitoring.view');

        // Localization
        Route::get('localization', [Controllers\LocalizationController::class, 'index'])
            ->name('localization')->middleware('permission:sysadmin.localization.manage');
        Route::put('localization', [Controllers\LocalizationController::class, 'update'])
            ->name('localization.update')->middleware('permission:sysadmin.localization.manage');

        // Backups — honest status page
        Route::get('backups', [Controllers\BackupController::class, 'index'])
            ->name('backups')->middleware('permission:sysadmin.backups.view');
    });
