<?php

namespace Modules\SystemAdministration\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Modules\SystemAdministration\Services\SettingCatalog;

class MaintenanceController extends Controller
{
    public function __construct(private SettingCatalog $catalog, private AuditService $audit) {}

    public function index()
    {
        return view('systemadministration::maintenance', [
            'readOnly' => (bool) $this->catalog->get('system.read_only_mode'),
            'down' => app()->isDownForMaintenance(),
            'bypassSecret' => \Illuminate\Support\Facades\Cache::get('sysadmin:down_secret'),
            'announcement' => $this->catalog->get('system.maintenance_announcement'),
        ]);
    }

    public function toggleReadOnly()
    {
        $new = ! (bool) $this->catalog->get('system.read_only_mode');
        $this->catalog->updateMany(['system.read_only_mode' => $new], 'Toggled via maintenance center');
        $this->audit->log('maintenance.readonly.'.($new ? 'enabled' : 'disabled'));

        return back()->with('success', $new ? 'Read-only mode ENABLED — mutating requests are blocked for non-privileged users.' : 'Read-only mode disabled.');
    }

    public function down(Request $request)
    {
        $secret = 'sa-'.Str::random(24);
        Artisan::call('down', [
            '--secret' => $secret,
            '--render' => 'errors::503',
        ]);
        \Illuminate\Support\Facades\Cache::put('sysadmin:down_secret', $secret, 3600);
        $this->audit->log('maintenance.mode.enabled', null, null, ['by' => $request->user()->email]);

        return back()->with('success', 'Application is DOWN for maintenance. Bypass URL: '.url("/{$secret}"));
    }

    public function up()
    {
        Artisan::call('up');
        \Illuminate\Support\Facades\Cache::forget('sysadmin:down_secret');
        $this->audit->log('maintenance.mode.disabled');

        return back()->with('success', 'Application is back UP.');
    }
}
