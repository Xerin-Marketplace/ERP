<?php

namespace Modules\Organization\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Organization\Http\Resources\BranchResource;
use Modules\Organization\Http\Resources\DepartmentResource;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\BranchAdministrator;
use Modules\Organization\Services\OrganizationService;

class BranchOperationsApiController extends ApiController
{
    use AuthorizesRequests;

    public function __construct(private OrganizationService $org) {}

    public function activate(Branch $branch)
    {
        $this->authorize('activate', $branch);

        return ApiResponse::success(
            new BranchResource($this->org->activateBranch($branch)),
            'Branch activated'
        );
    }

    public function deactivate(Branch $branch)
    {
        $this->authorize('deactivate', $branch);

        return ApiResponse::success(
            new BranchResource($this->org->deactivateBranch($branch)),
            'Branch deactivated — historical records preserved'
        );
    }

    public function departments(Request $request, Branch $branch)
    {
        $this->authorize('view', $branch);

        $data = $branch->departments()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy('name')
            ->paginate(min((int) $request->input('per_page', 15), 100));

        return ApiResponse::success(DepartmentResource::collection($data), 'Branch departments');
    }

    public function administrators(Branch $branch)
    {
        $this->authorize('view', $branch);

        return ApiResponse::success(
            $branch->administrators()->active()->with('user:id,name,email')->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'user' => ['id' => $a->user->id, 'name' => $a->user->name, 'email' => $a->user->email],
                    'assigned_at' => $a->created_at,
                ]),
            'Branch administrators'
        );
    }

    public function assignAdministrator(Request $request, Branch $branch)
    {
        $this->authorize('manageAdministrators', $branch);
        $data = $request->validate(['user_id' => ['required', 'exists:users,id']]);
        $admin = $this->org->assignBranchAdministrator($branch, $data['user_id'], $request->user()->id);

        return ApiResponse::success($admin->load('user:id,name,email'), 'Administrator assigned', 201);
    }

    public function revokeAdministrator(Branch $branch, BranchAdministrator $administrator)
    {
        $this->authorize('manageAdministrators', $branch);
        abort_unless($administrator->branch_id === $branch->id, 404);
        $this->org->revokeBranchAdministrator($administrator, request()->user()->id);

        return ApiResponse::success(null, 'Administrator revoked');
    }

    public function settings(Branch $branch)
    {
        $this->authorize('view', $branch);

        return ApiResponse::success($this->org->branchSettings($branch), 'Branch settings');
    }

    public function updateSettings(Request $request, Branch $branch)
    {
        $this->authorize('manageSettings', $branch);
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['present'],
        ]);

        foreach ($data['settings'] as $key => $value) {
            abort_unless(preg_match('/^[a-z0-9_.\-]{1,100}$/', $key), 422, "Invalid setting key: {$key}");
            $this->org->setBranchSetting($branch, $key, $value, $request->user()->id,
                is_array($value) ? 'json' : 'string');
        }

        return ApiResponse::success($this->org->branchSettings($branch->fresh()), 'Branch settings updated');
    }
}
