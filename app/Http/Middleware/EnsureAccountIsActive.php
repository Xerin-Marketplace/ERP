<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * Block suspended / deactivated / pending accounts from protected areas.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $status = $user?->status ?? AccountStatus::Active;

        if ($user && $status !== AccountStatus::Active) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is '.$status->value.'.',
                ], 403);
            }

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account is '.$status->value.'. Contact administration.']);
        }

        return $next($request);
    }
}
