<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function created(mixed $data = null, string $message = 'Created'): JsonResponse
    {
        return static::success($data, $message, 201);
    }

    public static function paginated(LengthAwarePaginator $paginator, string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    public static function error(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        $body = ['success' => false, 'message' => $message];
        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }
}
