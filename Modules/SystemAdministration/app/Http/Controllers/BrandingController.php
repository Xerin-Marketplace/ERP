<?php

namespace Modules\SystemAdministration\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SystemAdministration\Models\BrandingAsset;
use Modules\SystemAdministration\Services\BrandingService;
use Modules\SystemAdministration\Services\SettingCatalog;

class BrandingController extends Controller
{
    public function __construct(
        private BrandingService $branding,
        private SettingCatalog $catalog,
        private AuditService $audit,
    ) {}

    public function edit()
    {
        $defs = collect(SettingCatalog::definitions())->filter(fn ($d) => $d['group'] === 'Branding');

        return view('systemadministration::branding', [
            'defs' => $defs,
            'values' => $defs->mapWithKeys(fn ($d, $k) => [$k => $this->catalog->get($k)]),
            'slots' => BrandingService::SLOTS,
            'assets' => collect(BrandingService::SLOTS)->mapWithKeys(fn ($desc, $key) => [
                $key => ['current' => $this->branding->current($key), 'history' => $this->branding->history($key)],
            ]),
        ]);
    }

    public function update(Request $request)
    {
        $defs = collect(SettingCatalog::definitions())->filter(fn ($d) => $d['group'] === 'Branding');
        $input = array_intersect_key($request->input('settings', []), $defs->all());

        $changed = $this->catalog->updateMany($input, $request->input('reason'));
        $this->audit->log('branding.settings.updated', null, null, ['keys' => $changed]);

        return back()->with('success', count($changed).' branding setting(s) updated.');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate(['key' => 'required|string', 'file' => 'required|file']);

        $asset = $this->branding->upload($request->file('file'), $request->input('key'));
        $this->audit->log('branding.asset.uploaded', $asset, null, ['key' => $asset->key, 'path' => $asset->path]);

        return back()->with('success', 'Asset uploaded and activated.');
    }

    public function restoreLogo(BrandingAsset $asset)
    {
        $this->branding->restore($asset);
        $this->audit->log('branding.asset.restored', $asset, null, ['key' => $asset->key, 'path' => $asset->path]);

        return back()->with('success', 'Previous asset restored.');
    }
}
