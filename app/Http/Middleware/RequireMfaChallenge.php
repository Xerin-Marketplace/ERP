<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Modules\SystemAdministration\Services\MfaService;

/**
 * §16.4 — MFA enforcement for privileged roles.
 *
 * - Accounts with MFA enabled are challenged once per session
 *   (verified for VERIFICATION_TTL seconds after a successful code).
 * - Unenrolled privileged users (Finance/accounts/cashier, Release
 *   Approver, Branch Manager, CEO/management, admin tiers) are pushed
 *   to enrollment — the hard gate only applies while the
 *   `security.mfa.required` system setting is switched on, so the
 *   policy is admin-configurable rather than hard-coded.
 */
class RequireMfaChallenge
{
    private const SPEC_ROLES = [
        'branch-manager', 'Release Approver', 'TZ Operations Manager',
    ];

    private const SPEC_REGEX = '/finance|accountant|cashier|accounts|ceo|management|admin/i';

    private const EXEMPT = ['mfa.*', 'systemadmin.mfa.*', 'logout', 'password.force*'];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user) return $next($request);

        $mfa = app(MfaService::class);
        $record = $mfa->mfa($user);

        if ($record?->enabled) {
            if ($this->verified($request) || $request->routeIs(...self::EXEMPT)) {
                return $next($request);
            }
            return redirect()->guest(route('mfa.challenge'));
        }

        if ($this->policyRequiresMfa() && $this->privileged($mfa, $user)
            && ! $request->routeIs(...self::EXEMPT)) {
            return redirect()->guest(route('mfa.enroll'));
        }

        return $next($request);
    }

    private function verified(Request $request): bool
    {
        $at = $request->session()->get('mfa_verified_at');
        if (! $at) return false;
        if ($at === true) return true; // legacy boolean flag
        $ttl = (int) (SystemSetting::where('key', 'security.mfa.session_ttl_hours')->value('value') ?? 12) * 3600;
        return now()->timestamp - (int) $at < $ttl;
    }

    private function policyRequiresMfa(): bool
    {
        return SystemSetting::where('key', 'security.mfa.required')->value('value') === '1';
    }

    private function privileged(MfaService $mfa, $user): bool
    {
        if ($mfa->isRequired($user)) return true;
        foreach ($user->getRoleNames() as $role) {
            if (in_array($role, self::SPEC_ROLES, true) || preg_match(self::SPEC_REGEX, $role)) {
                return true;
            }
        }
        return false;
    }
}
