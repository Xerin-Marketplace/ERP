<?php

namespace Modules\SystemAdministration\Http\Controllers;

use App\Services\AuditService;
use App\Services\Navigation\NavigationRegistry;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SystemAdministration\Models\NavigationOverride;

class NavigationController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(NavigationRegistry $nav)
    {
        // Bypass overrides so the admin sees the full canonical tree.
        $items = $nav->all();
        $overrides = NavigationOverride::all()->keyBy('item_key');

        return view('systemadministration::navigation', [
            'groups' => $items->sortBy('order')->groupBy('group'),
            'overrides' => $overrides,
        ]);
    }

    public function update(Request $request, string $key)
    {
        $data = $request->validate([
            'label_override' => 'nullable|string|max:60',
            'order_override' => 'nullable|integer|min:0|max:999',
            'is_hidden' => 'nullable|boolean',
        ]);

        NavigationOverride::updateOrCreate(
            ['item_key' => $key],
            [
                'label_override' => $data['label_override'] ?: null,
                'order_override' => $data['order_override'] ?? null,
                'is_hidden' => (bool) ($data['is_hidden'] ?? false),
                'updated_by' => $request->user()->id,
            ]
        );

        NavigationOverride::flushCache();
        $this->audit->log('navigation.override.updated', null, null, ['item' => $key] + $data);

        return back()->with('success', 'Navigation item updated.');
    }

    public function reset()
    {
        NavigationOverride::truncate();
        NavigationOverride::flushCache();
        $this->audit->log('navigation.overrides.reset');

        return back()->with('success', 'All navigation overrides removed — defaults restored.');
    }
}
