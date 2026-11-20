<?php

namespace Modules\SystemAdministration\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SystemAdministration\Services\SettingCatalog;

class SettingsController extends Controller
{
    public function __construct(private SettingCatalog $catalog, private AuditService $audit) {}

    public function edit(string $group = 'General')
    {
        $defs = collect(SettingCatalog::definitions())->filter(fn ($d) => $d['group'] === $group);
        abort_if($defs->isEmpty(), 404);

        return view('systemadministration::settings', [
            'group' => $group,
            'groups' => ['General', 'Regional', 'Display'],
            'defs' => $defs,
            'values' => $defs->mapWithKeys(fn ($d, $k) => [$k => $this->catalog->get($k)]),
        ]);
    }

    public function update(Request $request, string $group)
    {
        $defs = collect(SettingCatalog::definitions())->filter(fn ($d) => $d['group'] === $group);
        abort_if($defs->isEmpty(), 404);

        $changed = $this->catalog->updateMany(array_intersect_key($request->input('settings', []), $defs->all()), $request->input('reason'));
        $this->audit->log("settings.{$group}.updated", null, null, ['keys' => $changed]);

        return back()->with('success', count($changed).' setting(s) updated.');
    }
}
