<?php

namespace Modules\Identity\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Modules\Identity\Enums\ApplicationStatus;
use Modules\Identity\Services\InvitationService;
use Modules\Identity\Services\ProvisioningService;

class ActivationController extends Controller
{
    public function __construct(
        private InvitationService $invitations,
        private ProvisioningService $provisioning,
    ) {}

    public function show(string $token)
    {
        try {
            $invitation = $this->invitations->resolve($token);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return view('identity::public.activate-invalid', [
                'message' => $e->errors()['token'][0] ?? 'Invalid activation link.',
            ]);
        }

        return view('identity::public.activate', [
            'token' => $token,
            'email' => $invitation->email,
            'expiresAt' => $invitation->expires_at,
        ]);
    }

    public function store(Request $request, string $token)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $invitation = $this->invitations->resolve($token);
        $user = $this->invitations->consume($invitation, $request->input('password'));

        // Application lifecycle: provisioning → active
        if ($application = $invitation->application) {
            $this->provisioning->markActivated($application);
        }

        $user->securityEvents()->create([
            'type' => 'activation_completed',
            'severity' => 'info',
            'ip_address' => $request->ip(),
        ]);
        app(\App\Services\AuditService::class)->log('user.activated_self', $user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'Your account is now active. Welcome to XERIN GROUP ERP.');
    }
}
