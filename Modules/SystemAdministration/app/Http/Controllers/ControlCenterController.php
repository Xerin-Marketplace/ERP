<?php

namespace Modules\SystemAdministration\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Identity\Models\AccountSecurityEvent;
use Modules\Identity\Models\LoginHistory;
use Modules\Identity\Models\RegistrationApplication;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;
use Modules\SystemAdministration\Models\FeatureFlag;
use Modules\SystemAdministration\Models\SystemIntegration;
use Modules\SystemAdministration\Services\HealthService;

class ControlCenterController extends Controller
{
    public function index(HealthService $health)
    {
        return view('systemadministration::control-center', [
            'platform' => [
                'users_total' => User::count(),
                'users_active' => User::where('status', 'active')->count(),
                'users_suspended' => User::where('status', 'suspended')->count(),
                'registrations_pending' => class_exists(RegistrationApplication::class)
                    ? RegistrationApplication::whereIn('status', ['submitted', 'pending_review', 'under_review'])->count() : 0,
                'companies' => Company::count(),
                'branches' => Branch::count(),
                'departments' => Department::count(),
                'modules_enabled' => count(array_filter(\Module::allEnabled() ? array_map(fn ($m) => true, \Module::allEnabled()) : [])),
                'modules_disabled' => count(\Module::allDisabled()),
                'sessions_active' => $health->activeSessions(),
                'failed_logins_24h' => class_exists(LoginHistory::class)
                    ? LoginHistory::where('event', 'failed')->where('created_at', '>=', now()->subDay())->count() : 0,
            ],
            'health' => $health->probes(),
            'security' => [
                'recent_events' => class_exists(AccountSecurityEvent::class)
                    ? AccountSecurityEvent::with('user:id,name')->latest()->limit(8)->get() : collect(),
                'failed_logins' => class_exists(LoginHistory::class)
                    ? LoginHistory::where('event', 'failed')->latest()->limit(8)->get() : collect(),
            ],
            'activity' => AuditLog::with('user:id,name')->latest()->limit(10)->get(),
            'flags' => FeatureFlag::count(),
            'integrations' => SystemIntegration::count(),
        ]);
    }
}
