<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthController extends ApiController
{
    /**
     * GET /api/v1/health — platform health probe.
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->check(fn () => DB::selectOne('select 1')),
            'cache' => $this->check(fn () => Cache::put('health:probe', 'ok', 5) && Cache::get('health:probe') === 'ok'),
            'storage' => $this->check(fn () => Storage::disk(config('filesystems.default'))->exists('.') !== null),
        ];

        $healthy = ! in_array('fail', array_column($checks, 'status'), true);

        return $this->ok([
            'status' => $healthy ? 'healthy' : 'degraded',
            'version' => app()->version(),
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 'Service healthy' : 'Service degraded');
    }

    private function check(callable $probe): array
    {
        try {
            $probe();

            return ['status' => 'ok'];
        } catch (\Throwable) {
            return ['status' => 'fail'];
        }
    }
}
