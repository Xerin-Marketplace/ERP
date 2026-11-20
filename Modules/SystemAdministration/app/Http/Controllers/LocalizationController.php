<?php

namespace Modules\SystemAdministration\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SystemAdministration\Services\SettingCatalog;

class LocalizationController extends Controller
{
    public function __construct(private SettingCatalog $catalog, private AuditService $audit) {}

    public function index()
    {
        $defs = SettingCatalog::definitions();

        return view('systemadministration::localization', [
            'locale' => $this->catalog->get('system.locale'),
            'available' => $this->catalog->get('system.available_locales'),
            'options' => $defs['system.available_locales']['options'],
        ]);
    }

    public function update(Request $request)
    {
        $changed = $this->catalog->updateMany(array_intersect_key($request->input('settings', []), array_flip(['system.locale', 'system.available_locales'])));
        $this->audit->log('localization.updated', null, null, ['keys' => $changed]);

        return back()->with('success', 'Localization settings updated.');
    }
}
