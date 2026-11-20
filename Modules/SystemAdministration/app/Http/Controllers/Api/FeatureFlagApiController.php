<?php

namespace Modules\SystemAdministration\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SystemAdministration\Services\FeatureFlagService;

class FeatureFlagApiController extends Controller
{
    public function check(Request $request, FeatureFlagService $flags, string $key)
    {
        return response()->json(['data' => ['key' => $key, 'enabled' => $flags->enabled($key, $request->user())]]);
    }
}
