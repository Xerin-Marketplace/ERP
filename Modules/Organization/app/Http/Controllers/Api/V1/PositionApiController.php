<?php

namespace Modules\Organization\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Modules\Employee\Models\JobPosition;
use Modules\Organization\Http\Requests\StorePositionRequest;
use Modules\Organization\Http\Requests\UpdatePositionRequest;
use Modules\Organization\Http\Resources\PositionResource;

class PositionApiController extends ApiController
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', JobPosition::class);

        $data = JobPosition::query()
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('company'), fn ($q) => $q->where('company_id', $request->input('company')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy('title')
            ->paginate(min((int) $request->input('per_page', 15), 100));

        return ApiResponse::success(PositionResource::collection($data), 'Positions');
    }

    public function store(StorePositionRequest $request)
    {
        $position = JobPosition::create($request->validated() + ['status' => 'active']);

        return ApiResponse::success(new PositionResource($position), 'Position created', 201);
    }

    public function show(JobPosition $position)
    {
        $this->authorize('view', $position);

        return ApiResponse::success(new PositionResource($position), 'Position');
    }

    public function update(UpdatePositionRequest $request, JobPosition $position)
    {
        $this->authorize('update', $position);
        $position->update($request->validated());

        return ApiResponse::success(new PositionResource($position->fresh()), 'Position updated');
    }
}
