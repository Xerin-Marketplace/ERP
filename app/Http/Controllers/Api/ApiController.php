<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    protected function ok(mixed $data = null, string $message = 'OK'): JsonResponse
    {
        return ApiResponse::success($data, $message);
    }

    protected function created(mixed $data = null, string $message = 'Created'): JsonResponse
    {
        return ApiResponse::created($data, $message);
    }

    protected function paginated(LengthAwarePaginator $paginator): JsonResponse
    {
        return ApiResponse::paginated($paginator);
    }

    protected function fail(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        return ApiResponse::error($message, $status, $errors);
    }
}
