<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SystemAdministration\Services\MfaService;

/** Session MFA challenge + self-service enrollment UI (spec §16.4). */
class MfaChallengeController extends Controller
{
    public function __construct(private MfaService $mfa) {}

    public function enroll(Request $r)
    {
        $mfa = $this->mfa->mfa($r->user());
        if ($mfa?->enabled) {
            return redirect()->route('dashboard')->with('status', 'MFA is already enabled on your account.');
        }
        return view('mfa.enroll', [
            'setup' => $mfa
                ? ['secret' => $mfa->secret, 'uri' => $this->uri($mfa->secret, $r->user()->email)]
                : $this->enrollSetup($r),
        ]);
    }

    public function confirmEnroll(Request $r)
    {
        $r->validate(['code' => 'required|string|max:8']);
        $codes = $this->mfa->confirmEnrollment($r->user(), $r->input('code'));
        $r->session()->put('mfa_verified_at', now()->timestamp);
        return view('mfa.recovery', ['codes' => $codes]);
    }

    public function challenge()
    {
        return view('mfa.challenge');
    }

    public function verify(Request $r)
    {
        $r->validate(['code' => 'required|string|max:12']);
        abort_unless($this->mfa->challenge($r->user(), $r->input('code')), 422, 'Invalid code.');
        $r->session()->put('mfa_verified_at', now()->timestamp);
        return redirect()->intended(route('dashboard'));
    }

    private function enrollSetup(Request $r): array
    {
        $setup = $this->mfa->beginEnrollment($r->user());
        return ['secret' => $setup['secret'], 'uri' => $setup['provisioning_uri']];
    }

    private function uri(string $secret, string $email): string
    {
        return 'otpauth://totp/XerinERP:'.urlencode($email)."?secret={$secret}&issuer=XerinERP";
    }
}
