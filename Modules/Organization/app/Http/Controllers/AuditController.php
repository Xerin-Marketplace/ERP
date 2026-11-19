<?php

namespace Modules\Organization\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;
use Modules\Organization\Models\Location;

class AuditController extends Controller
{
    private const RESOURCE_TYPES = [
        'company' => Company::class,
        'branch' => Branch::class,
        'location' => Location::class,
        'department' => Department::class,
    ];

    public function index(Request $request)
    {
        $logs = AuditLog::with('user:id,name')
            ->whereIn('resource_type', array_values(self::RESOURCE_TYPES))
            ->when($request->filled('resource'), fn ($q) => $q->where(
                'resource_type', self::RESOURCE_TYPES[$request->input('resource')] ?? 'none'
            ))
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', '%'.$request->input('action').'%'))
            ->when($request->filled('user'), fn ($q) => $q->where('user_id', $request->input('user')))
            ->latest()
            ->paginate(20)->withQueryString();

        return view('organization::audit', [
            'logs' => $logs,
            'resourceTypes' => array_keys(self::RESOURCE_TYPES),
        ]);
    }
}
