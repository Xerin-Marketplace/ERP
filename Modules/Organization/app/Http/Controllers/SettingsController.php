<?php

namespace Modules\Organization\Http\Controllers;

use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SettingsController extends Controller
{
    public function __construct(private SettingsService $settings) {}

    public function edit()
    {
        return view('organization::settings', [
            'defaults' => [
                'default_currency' => $this->settings->get('organization.default_currency', 'TZS'),
                'default_timezone' => $this->settings->get('organization.default_timezone', 'Africa/Dar_es_Salaam'),
                'default_country' => $this->settings->get('organization.default_country', 'Tanzania'),
                'require_branch_for_departments' => (bool) $this->settings->get('organization.require_branch_for_departments', false),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'default_currency' => ['required', 'string', 'size:3', 'alpha'],
            'default_timezone' => ['required', 'string', 'timezone'],
            'default_country' => ['required', 'string', 'max:100'],
            'require_branch_for_departments' => ['nullable', 'boolean'],
        ]);

        $this->settings->set('organization.default_currency', strtoupper($data['default_currency']), 'organization');
        $this->settings->set('organization.default_timezone', $data['default_timezone'], 'organization');
        $this->settings->set('organization.default_country', $data['default_country'], 'organization');
        $this->settings->set('organization.require_branch_for_departments', (bool) ($data['require_branch_for_departments'] ?? false), 'organization', 'bool');

        return back()->with('success', 'Organization settings updated.');
    }
}
