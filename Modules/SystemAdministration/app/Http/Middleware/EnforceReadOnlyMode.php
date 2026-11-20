<?php

namespace Modules\SystemAdministration\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global read-only mode (System > Maintenance). Blocks mutating HTTP verbs
 * for everyone except users holding sysadmin.maintenance.manage, so admins
 * always retain a recovery path. Logout stays reachable for all users.
 */
class EnforceReadOnlyMode
{
    private const EXEMPT = ['logout', 'login', 'up'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD') || $request->isMethod('OPTIONS')) {
            return $next($request);
        }

        if (in_array($request->path(), self::EXEMPT, true)) {
            return $next($request);
        }

        static $enabled = null;
        $enabled ??= rescue(fn () => app(SettingsService::class)->get('system.read_only_mode', false), false, report: false);

        if (! $enabled || $request->user()?->can('sysadmin.maintenance.manage')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'The system is in read-only maintenance mode.'], 423);
        }

        return back()->with('error', 'The system is in read-only maintenance mode — changes are temporarily disabled.');
    }
}
