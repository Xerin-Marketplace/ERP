<?php

namespace Modules\Reception\Services;

use Illuminate\Support\Facades\DB;
use Modules\Reception\Models\AccessApprovalTask;
use Modules\Reception\Models\AccessRequest;

class AccessDashboardService
{
    /** Reception KPIs — all real data. Optional branch scoping. */
    public function metrics(?int $branchId = null): array
    {
        $q = fn () => AccessRequest::query()
            ->when($branchId, fn ($x) => $x->where('branch_id', $branchId));

        return [
            'total'            => $q()->count(),
            'today'            => $q()->whereDate('submitted_at', today())->count(),
            'pending_reception'=> $q()->whereIn('status', ['submitted', 'reception_review'])->count(),
            'info_required'    => $q()->where('status', 'info_required')->count(),
            'in_approval'      => $q()->where('status', 'in_approval')->count(),
            'approved'         => $q()->where('status', 'approved')->count(),
            'provisioning'     => $q()->whereIn('status', ['provisioning'])->count(),
            'activated'        => $q()->whereIn('status', ['provisioned', 'activated'])->count(),
            'rejected'         => $q()->where('status', 'rejected')->count(),
            'overdue'          => AccessApprovalTask::where('status', 'pending')
                                    ->whereNotNull('due_at')->where('due_at', '<', now())->count(),
            'by_type'          => $q()->select('type', DB::raw('count(*) as n'))
                                    ->groupBy('type')->pluck('n', 'type'),
        ];
    }
}
