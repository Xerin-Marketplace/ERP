<?php

namespace Modules\SystemAdministration\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\SystemAdministration\Services\HealthService;

class MonitoringController extends Controller
{
    public function index(HealthService $health)
    {
        return view('systemadministration::monitoring', [
            'probes' => $health->probes(),
            'activeSessions' => $health->activeSessions(),
            'failedJobs' => DB::table('failed_jobs')->latest('failed_at')->limit(20)->get(),
            'pendingJobs' => DB::table('jobs')->count(),
        ]);
    }

    public function retryJob(string $id)
    {
        Artisan::call('queue:retry', ['id' => [$id]]);

        return back()->with('success', "Job {$id} re-queued.");
    }
}
