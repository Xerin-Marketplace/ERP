<?php

namespace Modules\SystemAdministration\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Real command definitions + handlers for the Command Center.
 * risk: low (execute) | medium (confirm) | high (approval) | critical (approval+reason)
 */
class CommandCatalog
{
    public static function definitions(): array
    {
        return [
            /* ---- System information ---- */
            'system.info' => [
                'name' => 'System Information', 'category' => 'System Information', 'risk' => 'low',
                'permission' => 'sysadmin.commands.execute',
                'description' => 'ERP name, version, environment, PHP/DB runtime.',
                'handler' => fn () => [
                    'app' => config('app.name'), 'env' => config('app.env'),
                    'php' => PHP_VERSION, 'laravel' => app()->version(),
                    'db' => config('database.default'), 'cache' => config('cache.default'),
                    'queue' => config('queue.default'),
                ],
            ],
            'system.time' => [
                'name' => 'Server Time', 'category' => 'System Information', 'risk' => 'low',
                'permission' => 'sysadmin.commands.execute',
                'description' => 'Server and application timezone.',
                'handler' => fn () => ['server' => now()->toDateTimeString(), 'tz' => date_default_timezone_get()],
            ],
            'system.modules.list' => [
                'name' => 'List Modules', 'category' => 'System Information', 'risk' => 'low',
                'permission' => 'sysadmin.commands.execute',
                'description' => 'Registered modules and enabled status.',
                'handler' => fn () => collect(\Module::all())
                    ->map(fn ($m) => ['name' => $m->getName(), 'enabled' => $m->isEnabled()])->values()->all(),
            ],

            /* ---- Health ---- */
            'system.health.check' => [
                'name' => 'Full Health Check', 'category' => 'System Health', 'risk' => 'low',
                'permission' => 'sysadmin.commands.execute',
                'description' => 'DB, cache, storage, queue status + latency.',
                'handler' => function () {
                    $probe = function (string $svc, callable $fn) {
                        $t = microtime(true);
                        try { $fn(); return ['service' => $svc, 'status' => 'ok', 'ms' => (int)((microtime(true)-$t)*1000)]; }
                        catch (\Throwable $e) { return ['service' => $svc, 'status' => 'fail', 'error' => $e->getMessage()]; }
                    };
                    return [
                        $probe('database', fn () => DB::select('select 1')),
                        $probe('cache', fn () => Cache::put('_hc', 1, 5)),
                        $probe('storage', fn () => Storage::disk('local')->put('_hc', '1')),
                        $probe('queue', fn () => DB::table('jobs')->count()),
                    ];
                },
            ],
            'system.health.database' => [
                'name' => 'Database Health', 'category' => 'System Health', 'risk' => 'low',
                'permission' => 'sysadmin.commands.execute',
                'description' => 'Connection test + table count.',
                'handler' => fn () => [
                    'driver' => DB::connection()->getDriverName(),
                    'tables' => count(DB::connection()->getSchemaBuilder()->getTables()),
                ],
            ],
            'queue.status' => [
                'name' => 'Queue Status', 'category' => 'Queue Management', 'risk' => 'low',
                'permission' => 'sysadmin.commands.execute',
                'description' => 'Pending and failed job counts.',
                'handler' => fn () => [
                    'pending' => DB::table('jobs')->count(),
                    'failed' => DB::table('failed_jobs')->count(),
                ],
            ],
            'queue.failed.retry-all' => [
                'name' => 'Retry All Failed Jobs', 'category' => 'Queue Management', 'risk' => 'medium',
                'permission' => 'sysadmin.commands.execute',
                'description' => 'Retry every failed queue job.',
                'parameters' => [],
                'handler' => fn () => ['output' => trim(Artisan::call('queue:retry', ['id' => ['all']]) ? 'done' : Artisan::output())],
            ],
            'cache.status' => [
                'name' => 'Cache Status', 'category' => 'Cache Management', 'risk' => 'low',
                'permission' => 'sysadmin.commands.execute',
                'description' => 'Which caches are currently compiled.',
                'handler' => fn () => [
                    'config' => file_exists(app()->getCachedConfigPath()),
                    'routes' => file_exists(app()->getCachedRoutesPath()),
                    'views' => count(glob(storage_path('framework/views/*.php')) ?: []),
                    'events' => file_exists(app()->getCachedEventsPath()),
                ],
            ],
            'cache.clear.application' => [
                'name' => 'Clear Application Cache', 'category' => 'Cache Management', 'risk' => 'medium',
                'permission' => 'sysadmin.commands.execute',
                'description' => 'php artisan cache:clear.',
                'handler' => function () { Artisan::call('cache:clear'); return ['done' => true]; },
            ],
            'cache.clear.config' => [
                'name' => 'Clear Config Cache', 'category' => 'Cache Management', 'risk' => 'medium',
                'permission' => 'sysadmin.commands.execute',
                'description' => 'php artisan config:clear.',
                'handler' => function () { Artisan::call('config:clear'); return ['done' => true]; },
            ],
            'cache.clear.views' => [
                'name' => 'Clear Compiled Views', 'category' => 'Cache Management', 'risk' => 'medium',
                'permission' => 'sysadmin.commands.execute',
                'description' => 'php artisan view:clear.',
                'handler' => function () { Artisan::call('view:clear'); return ['done' => true]; },
            ],
            'cache.clear.permissions' => [
                'name' => 'Clear Permission Cache', 'category' => 'Cache Management', 'risk' => 'medium',
                'permission' => 'sysadmin.commands.execute',
                'description' => 'php artisan permission:cache-reset.',
                'handler' => function () { Artisan::call('permission:cache-reset'); return ['done' => true]; },
            ],
            'database.migrations.status' => [
                'name' => 'Migration Status', 'category' => 'Database Operations', 'risk' => 'low',
                'permission' => 'sysadmin.commands.execute',
                'description' => 'Pending vs ran migrations.',
                'handler' => function () {
                    Artisan::call('migrate:status');
                    return ['output' => Artisan::output()];
                },
            ],
            'users.sessions.revoke' => [
                'name' => 'Revoke User Sessions', 'category' => 'User Administration', 'risk' => 'high',
                'permission' => 'sysadmin.commands.execute',
                'requires_approval' => true,
                'description' => 'Invalidate all sessions for a user (session revoke + token delete).',
                'parameters' => ['user_id' => 'required|integer|exists:users,id'],
                'handler' => function (array $p) {
                    \App\Models\User::findOrFail($p['user_id'])->tokens()->delete();
                    return ['tokens_revoked' => true, 'user_id' => $p['user_id']];
                },
            ],
            'users.suspend' => [
                'name' => 'Suspend User', 'category' => 'User Administration', 'risk' => 'high',
                'permission' => 'sysadmin.commands.execute',
                'requires_approval' => true,
                'description' => 'Suspend an account via Identity lifecycle service.',
                'parameters' => ['user_id' => 'required|integer|exists:users,id'],
                'handler' => function (array $p) {
                    $u = \App\Models\User::findOrFail($p['user_id']);
                    $u->update(['status' => 'suspended']);
                    $u->tokens()->delete();
                    return ['suspended' => $u->id];
                },
            ],
            'maintenance.enable' => [
                'name' => 'Enable Maintenance Mode', 'category' => 'Maintenance', 'risk' => 'high',
                'permission' => 'sysadmin.commands.execute',
                'requires_approval' => true,
                'description' => 'php artisan down (read-only mode alternative recommended).',
                'parameters' => ['message' => 'nullable|string|max:200'],
                'handler' => function (array $p) {
                    Artisan::call('down', array_filter(['--render' => null, '--retry' => 60, '--secret' => 'sysadmin-bypass-'.Str::random(8)]));
                    return ['down' => true];
                },
            ],
            'maintenance.disable' => [
                'name' => 'Disable Maintenance Mode', 'category' => 'Maintenance', 'risk' => 'medium',
                'permission' => 'sysadmin.commands.execute',
                'description' => 'php artisan up.',
                'handler' => function () { Artisan::call('up'); return ['up' => true]; },
            ],
            'security.audit.integrity' => [
                'name' => 'Audit Integrity Check', 'category' => 'Security', 'risk' => 'low',
                'permission' => 'sysadmin.commands.execute',
                'description' => 'audit_logs count, latest entry, gaps check.',
                'handler' => fn () => [
                    'records' => DB::table('audit_logs')->count(),
                    'latest' => DB::table('audit_logs')->max('created_at'),
                ],
            ],
            'reports.system.overview' => [
                'name' => 'System Overview Report', 'category' => 'System Information', 'risk' => 'low',
                'permission' => 'sysadmin.commands.execute',
                'description' => 'Counts: users, branches, modules, roles, perms.',
                'handler' => fn () => [
                    'users' => \App\Models\User::count(),
                    'branches' => DB::table('branches')->count(),
                    'roles' => DB::table('roles')->count(),
                    'permissions' => DB::table('permissions')->count(),
                    'audit_records' => DB::table('audit_logs')->count(),
                ],
            ],
        ];
    }
}
