<?php

namespace Modules\SystemAdministration\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SystemAdministration\Models\SettingVersion;
use Modules\SystemAdministration\Services\SettingCatalog;

class RulesController extends Controller
{
    public function __construct(private SettingCatalog $catalog, private AuditService $audit) {}

    public function index(Request $request)
    {
        $defs = collect(SettingCatalog::definitions())
            ->filter(fn ($d) => str_starts_with($d['group'], 'Rules'))
            ->groupBy('group');

        return view('systemadministration::rules', [
            'groups' => $defs,
            'values' => $defs->map(fn ($items) => $items->mapWithKeys(fn ($d, $k) => [$k => $this->catalog->get($k)])),
            'history' => SettingVersion::with('changedBy:id,name')->latest()->limit(20)->get(),
            'selected' => $request->query('g', $defs->keys()->first()),
        ]);
    }

    public function update(Request $request)
    {
        $keys = collect(SettingCatalog::definitions())
            ->filter(fn ($d) => str_starts_with($d['group'], 'Rules'))
            ->keys()->all();

        $changed = $this->catalog->updateMany(array_intersect_key($request->input('settings', []), array_flip($keys)), $request->input('reason'));
        $this->audit->log('rules.updated', null, null, ['keys' => $changed, 'reason' => $request->input('reason')]);

        return back()->with('success', count($changed).' rule(s) updated.');
    }
}
