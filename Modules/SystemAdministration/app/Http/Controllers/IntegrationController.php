<?php

namespace Modules\SystemAdministration\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SystemAdministration\Models\SystemIntegration;

class IntegrationController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index()
    {
        return view('systemadministration::integrations', [
            'integrations' => SystemIntegration::with('updatedBy:id,name')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key' => 'required|string|max:60|alpha_dash|unique:system_integrations,key',
            'name' => 'required|string|max:120',
            'driver' => 'required|in:smtp,sms,payment,storage,whatsapp,custom',
        ]);

        $i = SystemIntegration::create($data + ['is_enabled' => false]);
        $this->audit->log('integration.created', $i, null, ['key' => $i->key]);

        return back()->with('success', "Integration {$i->name} registered — configure credentials before enabling.");
    }

    public function update(Request $request, SystemIntegration $integration)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'endpoint' => 'nullable|url|max:255',
            'username' => 'nullable|string|max:190',
            'secret' => 'nullable|string|max:190',
            'extra' => 'nullable|json',
            'is_enabled' => 'nullable|boolean',
        ]);

        $config = $integration->configArray();
        foreach (['endpoint', 'username', 'extra'] as $f) {
            if (array_key_exists($f, $data) && $data[$f] !== null && $data[$f] !== '') {
                $config[$f] = $data[$f];
            }
        }
        // Only replace the secret when a new one is supplied — never echo it back.
        if (! empty($data['secret'])) {
            $config['secret'] = $data['secret'];
        }

        $integration->setConfig($config);
        $integration->name = $data['name'];
        $integration->is_enabled = (bool) ($data['is_enabled'] ?? false);
        $integration->updated_by = $request->user()->id;
        $integration->save();

        $this->audit->log('integration.updated', $integration, null, ['key' => $integration->key, 'enabled' => $integration->is_enabled]);

        return back()->with('success', 'Integration updated.');
    }

    public function toggle(SystemIntegration $integration)
    {
        $integration->update(['is_enabled' => ! $integration->is_enabled, 'updated_by' => auth()->id()]);
        $this->audit->log('integration.toggled', $integration, null, ['enabled' => $integration->is_enabled]);

        return back()->with('success', $integration->is_enabled ? 'Integration enabled.' : 'Integration disabled.');
    }

    public function test(SystemIntegration $integration)
    {
        $config = $integration->configArray();
        $endpoint = $config['endpoint'] ?? null;

        if (! $endpoint) {
            $integration->update(['last_tested_at' => now(), 'last_status' => 'fail', 'last_error' => 'No endpoint configured.']);
            return back()->with('error', 'No endpoint configured — test cannot run.');
        }

        try {
            $ctx = stream_context_create(['http' => ['timeout' => 5]]);
            $code = @get_headers($endpoint, false, $ctx) ? 200 : 0;
            $ok = $code > 0;
            $integration->update([
                'last_tested_at' => now(),
                'last_status' => $ok ? 'ok' : 'fail',
                'last_error' => $ok ? null : 'Endpoint unreachable',
            ]);
            return back()->with($ok ? 'success' : 'error', $ok ? 'Connection OK — endpoint responded.' : 'Endpoint unreachable.');
        } catch (\Throwable $e) {
            $integration->update(['last_tested_at' => now(), 'last_status' => 'fail', 'last_error' => 'Connection error']);
            return back()->with('error', 'Connection test failed: endpoint unreachable.');
        }
    }

    public function destroy(SystemIntegration $integration)
    {
        $integration->delete();
        $this->audit->log('integration.deleted', $integration);
        return back()->with('success', 'Integration removed.');
    }
}
