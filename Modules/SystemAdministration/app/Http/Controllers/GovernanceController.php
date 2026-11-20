<?php

namespace Modules\SystemAdministration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SystemAdministration\Models\IntegrationTestResult;
use Modules\SystemAdministration\Models\SettingChangeRequest;
use Modules\SystemAdministration\Models\SystemIntegration;
use Modules\SystemAdministration\Services\{IntegrationTester, MfaService, ScopedSettingsResolver, SettingChangeService};

class GovernanceController extends Controller
{
    /** GET scoped/effective setting for current user. */
    public function effective(string $key, Request $r, ScopedSettingsResolver $svc)
    {
        abort_unless($r->user()->can('sysadmin.settings.manage') || $r->user()->can('sysadmin.dashboard.view'), 403);
        return response()->json($svc->resolve($key, $r->user(), $r->only('branch_id','company_id','group_id')));
    }

    public function setScope(string $key, Request $r, ScopedSettingsResolver $svc)
    {
        abort_unless($r->user()->can('sysadmin.settings.manage'), 403);
        $d = $r->validate(['scope_type' => 'required|in:group,company,branch,user',
            'scope_id' => 'required|integer', 'value' => 'required']);
        $row = $svc->set($key, $d['scope_type'], (int)$d['scope_id'], $d['value'], $r->user());
        return response()->json($row);
    }

    public function requestChange(Request $r, SettingChangeService $svc)
    {
        abort_unless($r->user()->can('sysadmin.settings.manage'), 403);
        $d = $r->validate(['key' => 'required|string', 'value' => 'required',
            'reason' => 'required|string|max:500']);
        return response()->json($svc->requestChange($d['key'], $d['value'], $r->user(), $d['reason']), 201);
    }

    public function approve(Request $r, SettingChangeRequest $cr, SettingChangeService $svc)
    {
        abort_unless($r->user()->can('sysadmin.settings.manage'), 403);
        return response()->json($svc->approve($cr, $r->user(), $r->input('comment')));
    }

    public function reject(Request $r, SettingChangeRequest $cr, SettingChangeService $svc)
    {
        abort_unless($r->user()->can('sysadmin.settings.manage'), 403);
        $svc->reject($cr, $r->user(), $r->input('comment'));
        return response()->json(['status' => 'rejected']);
    }

    public function pendingChanges(Request $r)
    {
        abort_unless($r->user()->can('sysadmin.settings.manage'), 403);
        return response()->json(SettingChangeRequest::with('requester:id,name')->where('status','pending')->latest()->get());
    }

    public function testIntegration(Request $r, SystemIntegration $integration, IntegrationTester $svc)
    {
        abort_unless($r->user()->can('sysadmin.integrations.manage'), 403);
        $res = $svc->test($integration, $r->user()->id);
        $integration->update(['last_tested_at' => now(), 'last_status' => $res->status,
            'last_error' => $res->status === 'successful' ? null : $res->message]);
        return response()->json($res);
    }

    public function integrationHistory(SystemIntegration $integration, Request $r)
    {
        abort_unless($r->user()->can('sysadmin.integrations.manage'), 403);
        return response()->json(IntegrationTestResult::where('integration_id', $integration->id)->latest()->limit(20)->get());
    }

    public function mfaEnroll(Request $r, MfaService $svc)
    {
        return response()->json($svc->beginEnrollment($r->user()));
    }

    public function mfaConfirm(Request $r, MfaService $svc)
    {
        $r->validate(['code' => 'required|string']);
        return response()->json(['recovery_codes' => $svc->confirmEnrollment($r->user(), $r->input('code'))]);
    }

    public function mfaChallenge(Request $r, MfaService $svc)
    {
        $r->validate(['code' => 'required|string']);
        abort_unless($svc->challenge($r->user(), $r->input('code')), 422, 'Invalid code.');
        $r->session()->put('mfa_verified_at', now()->timestamp);
        return response()->json(['mfa_verified' => true]);
    }
}
