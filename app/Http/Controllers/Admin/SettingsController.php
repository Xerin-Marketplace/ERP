<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(private SettingsService $settings) {}

    public function edit()
    {
        return view('admin.settings', [
            'values' => [
                'app_name' => $this->settings->get('system.app_name', config('app.name')),
                'timezone' => $this->settings->get('system.timezone', config('app.timezone')),
                'locale' => $this->settings->get('system.locale', config('app.locale')),
                'date_format' => $this->settings->get('system.date_format', 'Y-m-d'),
                'records_per_page' => (int) $this->settings->get('system.records_per_page', 15),
                'session_lifetime' => (int) $this->settings->get('system.session_lifetime', config('session.lifetime')),
                'require_email_verification' => (bool) $this->settings->get('system.require_email_verification', true),
                'password_min_length' => (int) $this->settings->get('system.password_min_length', 8),
            ],
        ]);
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->can('settings.manage'), 403);

        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'timezone' => ['required', 'string', 'timezone'],
            'locale' => ['required', 'string', 'in:en,sw,fr'],
            'date_format' => ['required', 'string', 'in:Y-m-d,d/m/Y,m/d/Y,d M Y'],
            'records_per_page' => ['required', 'integer', 'in:10,15,25,50,100'],
            'session_lifetime' => ['required', 'integer', 'min:5', 'max:1440'],
            'require_email_verification' => ['nullable', 'boolean'],
            'password_min_length' => ['required', 'integer', 'min:6', 'max:64'],
        ]);

        $this->settings->set('system.app_name', $data['app_name'], 'system');
        $this->settings->set('system.timezone', $data['timezone'], 'system');
        $this->settings->set('system.locale', $data['locale'], 'system');
        $this->settings->set('system.date_format', $data['date_format'], 'system');
        $this->settings->set('system.records_per_page', $data['records_per_page'], 'system', 'int');
        $this->settings->set('system.session_lifetime', $data['session_lifetime'], 'system', 'int');
        $this->settings->set('system.require_email_verification', (bool) ($data['require_email_verification'] ?? false), 'system', 'bool');
        $this->settings->set('system.password_min_length', $data['password_min_length'], 'system', 'int');

        return back()->with('success', 'System settings updated.');
    }
}
