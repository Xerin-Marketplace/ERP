<?php

namespace Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Identity\Services\AdminDelegationService;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\BranchAdministrator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminConsoleController extends Controller
{
    public function __construct(private AdminDelegationService $delegation) {}

    /** Global admin overview — super-admin sees all branches; branch-admin sees only theirs. */
    public function dashboard(Request $request)
    {
        abort_unless($request->user()->can('admin.console.view'), 403);
        $branchId = $this->delegation->isSuperAdmin($request->user())
            ? null : $this->delegation->adminBranchId($request->user());
        $scoped = fn ($q) => $branchId ? $q->where('branch_id', $branchId) : $q;

        return view('identity::admin.dashboard', [
            'isGlobal' => $branchId === null,
            'branch' => $branchId ? Branch::find($branchId) : null,
            'branches' => $branchId ? collect() : Branch::orderBy('name')->get(['id','name','code']),
            'usersTotal' => $scoped(User::query())->count(),
            'usersActive' => $scoped(User::where('status', 'active'))->count(),
            'usersSuspended' => $scoped(User::where('status', 'suspended'))->count(),
            'branchAdmins' => $scoped(BranchAdministrator::whereNull('revoked_at'))->with('user:id,name,email','branch:id,name')->get(),
            'roleDistribution' => Role::withCount('users')->orderBy('name')->get(['id','name']),
        ]);
    }

    public function branchAdmins(Request $request)
    {
        $this->assertGlobal($request);
        return view('identity::admin.branch-admins', [
            'delegations' => BranchAdministrator::with('user:id,name,email', 'branch:id,name', 'assigner:id,name')->latest()->paginate(20),
            'branches' => Branch::orderBy('name')->get(['id','name']),
            'users' => User::where('status', 'active')->orderBy('name')->get(['id','name','email','branch_id']),
            'roles' => Role::whereNotIn('name', AdminDelegationService::GLOBAL_ROLES)->orderBy('name')->pluck('name'),
            'permissions' => Permission::orderBy('name')->pluck('name'),
        ]);
    }

    public function assignBranchAdmin(Request $request)
    {
        $this->assertGlobal($request);
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'delegable_roles' => 'nullable|array', 'delegable_roles.*' => 'exists:roles,name',
            'delegable_permissions' => 'nullable|array', 'delegable_permissions.*' => 'exists:permissions,name',
            'expires_at' => 'nullable|date|after:now',
        ]);
        $this->delegation->assignBranchAdmin($request->user(), User::findOrFail($data['user_id']),
            (int) $data['branch_id'], $data['delegable_roles'] ?? [], $data['delegable_permissions'] ?? [],
            isset($data['expires_at']) ? \Illuminate\Support\Carbon::parse($data['expires_at']) : null);
        return back()->with('success', 'Branch administrator delegated.');
    }

    public function suspendBranchAdmin(Request $request, BranchAdministrator $delegation)
    {
        $this->assertGlobal($request);
        $this->delegation->suspend($request->user(), $delegation);
        return back()->with('success', 'Delegation suspended.');
    }

    public function reactivateBranchAdmin(Request $request, BranchAdministrator $delegation)
    {
        $this->assertGlobal($request);
        $this->delegation->reactivate($request->user(), $delegation);
        return back()->with('success', 'Delegation reactivated.');
    }

    public function revokeBranchAdmin(Request $request, BranchAdministrator $delegation)
    {
        $this->assertGlobal($request);
        $this->delegation->revoke($request->user(), $delegation);
        return back()->with('success', 'Delegation revoked.');
    }

    private function assertGlobal(Request $request): void
    {
        abort_unless($request->user()->hasRole('super-admin'), 403);
    }
}
