<?php

namespace Modules\SystemAdministration\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\SystemAdministration\Models\FeatureFlag;
use Modules\SystemAdministration\Services\FeatureFlagService;

class FeatureFlagController extends Controller
{
    public function __construct(private AuditService $audit, private FeatureFlagService $flags) {}

    public function index()
    {
        return view('systemadministration::feature-flags', [
            'flags' => FeatureFlag::with('updatedBy:id,name')->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key' => 'required|string|max:80|alpha_dash|unique:system_feature_flags,key',
            'label' => 'required|string|max:120',
            'description' => 'nullable|string|max:255',
            'is_enabled' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);
        $data['key'] = Str::lower($data['key']);
        $data['updated_by'] = $request->user()->id;

        $flag = FeatureFlag::create($data);
        $this->flags->flush();
        $this->audit->log('feature_flag.created', $flag, null, $data);

        return back()->with('success', "Flag {$flag->key} created.");
    }

    public function update(Request $request, FeatureFlag $flag)
    {
        $data = $request->validate([
            'label' => 'required|string|max:120',
            'description' => 'nullable|string|max:255',
            'is_enabled' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);
        $data['is_enabled'] = (bool) ($data['is_enabled'] ?? false);
        $data['updated_by'] = $request->user()->id;

        $flag->update($data);
        $this->flags->flush();
        $this->audit->log('feature_flag.updated', $flag, null, $data);

        return back()->with('success', "Flag {$flag->key} updated.");
    }

    public function destroy(FeatureFlag $flag)
    {
        $flag->delete();
        $this->flags->flush();
        $this->audit->log('feature_flag.deleted', $flag);

        return back()->with('success', "Flag {$flag->key} deleted.");
    }
}
