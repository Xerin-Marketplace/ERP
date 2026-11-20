<?php

namespace Modules\SystemAdministration\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\SystemAdministration\Services\HealthService;

class DashboardApiController extends Controller
{
    public function show()
    {
        return response()->json(['data' => [
            'users_total' => \App\Models\User::count(),
            'sessions_active' => app(HealthService::class)->activeSessions(),
            'modules_enabled' => count(\Module::allEnabled()),
            'timestamp' => now()->toIso8601String(),
        ]]);
    }

    public function health(HealthService $health)
    {
        return response()->json(['data' => $health->probes()]);
    }
}
