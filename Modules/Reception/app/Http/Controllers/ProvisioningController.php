<?php

namespace Modules\Reception\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Identity\Services\RoleAssignmentGuard;
use Modules\Reception\Models\AccessRequest;
use Modules\Reception\Services\AccessProvisioningService;
use Spatie\Permission\Models\Role;

class ProvisioningController extends Controller
{
    public function __construct(
        private AccessProvisioningService $provisioning,
        private RoleAssignmentGuard $roleGuard,
    ) {}

    public function queue(Request $r)
    {
        return view('reception::access.provisioning.queue', [
            'requests' => $this->provisioning->queue(),
        ]);
    }

    public function configure(Request $r, AccessRequest $accessRequest)
    {
        abort_unless($accessRequest->status === 'approved', 403);
        return view('reception::access.provisioning.configure', [
            'request' => $accessRequest->load('employee.user', 'sponsor', 'history'),
            'assignableRoles' => $this->roleGuard->assignableRoles(
                $r->user(), Role::orderBy('name')->get()),
        ]);
    }

    public function provision(Request $r, AccessRequest $accessRequest)
    {
        $data = $r->validate([
            'roles' => 'nullable|array', 'roles.*' => 'exists:roles,name',
            'category' => 'nullable|string|max:40',
            'confirm' => 'accepted',
        ]);
        $user = $this->provisioning->provision($accessRequest, $data, $r->user());
        return redirect()->route('reception.access.provisioning.queue')
            ->with('success', "Account provisioned for {$user->email}. Invitation sent.");
    }
}
