<?php

namespace Modules\Identity\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProfileController extends Controller
{
    /** Personalized identity page — /identity/me */
    public function show(Request $request)
    {
        $user = $request->user()->load([
            'profile', 'branch.company:id,legal_name',
            'currentAssignment.company:id,legal_name',
            'currentAssignment.branch:id,name',
            'currentAssignment.department:id,name',
            'currentAssignment.manager:id,name',
            'assignments.company:id,legal_name', 'assignments.branch:id,name',
            'assignments.department:id,name',
            'roles.permissions',
            'loginHistories' => fn ($q) => $q->limit(10),
            'unreadNotifications' => fn ($q) => $q->limit(10),
        ]);

        return view('identity::me', compact('user'));
    }
}
