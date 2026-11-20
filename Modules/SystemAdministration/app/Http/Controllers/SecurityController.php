<?php

namespace Modules\SystemAdministration\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Identity\Models\AccountSecurityEvent;
use Modules\Identity\Models\LoginHistory;

class SecurityController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index()
    {
        $sessions = DB::table('sessions')->orderByDesc('last_activity')->limit(50)->get();
        $userIds = $sessions->pluck('user_id')->filter()->unique();
        $users = User::whereIn('id', $userIds)->pluck('name', 'id');

        return view('systemadministration::security', [
            'sessions' => $sessions,
            'sessionUsers' => $users,
            'loginHistory' => class_exists(LoginHistory::class) ? LoginHistory::with('user:id,name')->latest()->limit(50)->get() : collect(),
            'securityEvents' => class_exists(AccountSecurityEvent::class) ? AccountSecurityEvent::with('user:id,name')->latest()->limit(50)->get() : collect(),
        ]);
    }

    public function revokeSession(Request $request)
    {
        $data = $request->validate(['session_id' => 'required|string']);
        DB::table('sessions')->where('id', $data['session_id'])->delete();
        $this->audit->log('security.session.revoked', null, null, ['session' => substr($data['session_id'], 0, 8).'…']);

        return back()->with('success', 'Session revoked.');
    }

    public function revokeUserSessions(User $user)
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $this->audit->log('security.sessions.revoked_all', $user, null, ['user' => $user->email]);

        return back()->with('success', "All sessions revoked for {$user->name}.");
    }
}
