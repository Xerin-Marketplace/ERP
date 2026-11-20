<?php

namespace Modules\SystemAdministration\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Real platform health probes — every metric comes from the live system.
 */
class HealthService
{
    public function probes(): array
    {
        return [
            'database' => $this->probeDatabase(),
            'cache' => $this->probeCache(),
            'queue' => $this->probeQueue(),
            'storage' => $this->probeStorage(),
        ];
    }

    private function probeDatabase(): array
    {
        $start = microtime(true);
        try {
            DB::selectOne('select 1');
            $ms = round((microtime(true) - $start) * 1000, 1);
            return ['status' => 'ok', 'detail' => "{$ms} ms response"];
        } catch (\Throwable $e) {
            return ['status' => 'fail', 'detail' => 'connection failed'];
        }
    }

    private function probeCache(): array
    {
        try {
            Cache::put('sysadmin:probe', 1, 5);
            return ['status' => Cache::get('sysadmin:probe') === 1 ? 'ok' : 'fail', 'detail' => config('cache.default').' store'];
        } catch (\Throwable) {
            return ['status' => 'fail', 'detail' => 'cache write failed'];
        }
    }

    private function probeQueue(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();
            return [
                'status' => $failed > 0 ? 'warn' : 'ok',
                'detail' => "{$pending} pending · {$failed} failed",
                'pending' => $pending,
                'failed' => $failed,
            ];
        } catch (\Throwable) {
            return ['status' => 'warn', 'detail' => config('queue.default').' (tables unavailable)'];
        }
    }

    private function probeStorage(): array
    {
        try {
            $disk = config('filesystems.default');
            Storage::disk($disk)->exists('.');
            $free = @disk_free_space(storage_path());
            $total = @disk_total_space(storage_path());
            $pct = $total ? round((1 - $free / $total) * 100, 1) : null;
            return ['status' => 'ok', 'detail' => $pct !== null ? "{$pct}% used" : $disk.' disk', 'usage_pct' => $pct];
        } catch (\Throwable) {
            return ['status' => 'fail', 'detail' => 'storage probe failed'];
        }
    }

    public function activeSessions(): int
    {
        try {
            return DB::table('sessions')->where('last_activity', '>=', now()->subMinutes(15)->timestamp)->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
