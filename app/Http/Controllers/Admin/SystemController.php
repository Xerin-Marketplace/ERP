<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SystemController extends Controller
{
    public function index()
    {
        return view('admin.system', [
            'info' => [
                'App' => config('app.name'),
                'Laravel' => app()->version(),
                'PHP' => PHP_VERSION,
                'Environment' => app()->environment(),
                'Organization' => config('erp.organization'),
                'Timezone' => config('app.timezone'),
                'Locale' => app()->getLocale(),
            ],
            'health' => [
                'Database' => $this->probe(fn () => DB::selectOne('select 1')),
                'Cache' => $this->probe(fn () => Cache::put('sys:probe', 1, 5) && Cache::get('sys:probe')),
                'Storage' => $this->probe(fn () => Storage::disk(config('filesystems.default'))->exists('.') !== null),
                'Queue' => config('queue.default'),
            ],
            'counts' => [
                'users' => User::count(),
                'audit_events' => AuditLog::count(),
            ],
            'recentAudit' => AuditLog::with('user')->latest()->limit(10)->get(),
        ]);
    }

    private function probe(callable $check): string
    {
        try {
            return $check() ? 'ok' : 'fail';
        } catch (\Throwable) {
            return 'fail';
        }
    }
}
