<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Users flagged must_change_password are confined to the change screen until they set a new password. */
class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user?->must_change_password
            && ! $request->routeIs('password.force*', 'logout', 'password.update')) {
            return redirect()->route('password.force');
        }
        return $next($request);
    }
}
