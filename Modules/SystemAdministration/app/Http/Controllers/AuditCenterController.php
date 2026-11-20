<?php

namespace Modules\SystemAdministration\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditCenterController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user:id,name')->latest();

        if ($request->filled('q')) {
            $query->where('action', 'like', '%'.$request->q.'%');
        }
        if ($request->filled('user')) {
            $query->where('user_id', $request->user);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }
        if ($request->filled('resource')) {
            $query->where('resource_type', 'like', '%'.$request->resource.'%');
        }

        return view('systemadministration::audit', [
            'logs' => $query->paginate(30)->withQueryString(),
            'users' => \App\Models\User::orderBy('name')->pluck('name', 'id'),
            'filters' => $request->only('q', 'user', 'from', 'to', 'resource'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->can('sysadmin.audit.view'), 403);

        $query = AuditLog::with('user:id,name')->latest()->limit(5000);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'user', 'action', 'resource', 'ip', 'created_at']);
            foreach ($query->cursor() as $log) {
                fputcsv($out, [
                    $log->id,
                    $log->user?->name ?? 'system',
                    $log->action,
                    $log->resource_type ? class_basename($log->resource_type).'#'.$log->resource_id : '',
                    $log->ip_address,
                    $log->created_at,
                ]);
            }
            fclose($out);
        }, 'audit-export-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }
}
