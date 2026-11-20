<?php

namespace Modules\SystemAdministration\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SystemAdministration\Services\SettingCatalog;

class SettingsApiController extends Controller
{
    public function index(SettingCatalog $catalog)
    {
        $values = collect(SettingCatalog::definitions())->map(fn ($d, $k) => [
            'group' => $d['group'],
            'label' => $d['label'],
            'type' => $d['type'],
            'risk' => $d['risk'],
            'value' => $catalog->get($k),
        ]);

        return response()->json(['data' => $values]);
    }

    public function update(Request $request, SettingCatalog $catalog)
    {
        $changed = $catalog->updateMany($request->input('settings', []), $request->input('reason'));
        app(\App\Services\AuditService::class)->log('api.settings.updated', null, null, ['keys' => $changed]);

        return response()->json(['data' => ['changed' => $changed]]);
    }
}
