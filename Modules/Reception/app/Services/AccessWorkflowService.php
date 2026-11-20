<?php

namespace Modules\Reception\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Reception\Models\AccessApprovalTask;
use Modules\Reception\Models\AccessRequest;
use Modules\Reception\Models\AccessWorkflow;
use Modules\Reception\Models\AccessWorkflowStage;
use Modules\Reception\Models\AccessWorkflowVersion;

class AccessWorkflowService
{
    /**
     * Resolution order: exact branch+department match → branch → department →
     * type wildcard (null scoping). Most specific wins.
     */
    public function resolve(AccessRequest $request): ?AccessWorkflowVersion
    {
        $candidates = AccessWorkflow::where('is_active', true)
            ->where(fn ($q) => $q->where('request_type', $request->type)->orWhere('request_type', '*'))
            ->get()
            ->sortByDesc(fn ($w) => ($w->request_type === $request->type ? 4 : 0)
                + ($w->branch_id === $request->branch_id ? 2 : 0)
                + ($w->department_id === $request->department_id ? 1 : 0));

        foreach ($candidates as $w) {
            if ($w->branch_id && $w->branch_id !== $request->branch_id) continue;
            if ($w->department_id && $w->department_id !== $request->department_id) continue;
            $version = AccessWorkflowVersion::where('workflow_id', $w->id)
                ->where('version', $w->current_version)->where('status', 'published')->first();
            if ($version) return $version->load('stages');
        }
        return null;
    }

    /** Create pending tasks for a stage sequence (sequential mode). */
    public function emitStageTasks(AccessRequest $request, int $sequence): void
    {
        $stages = AccessWorkflowStage::where('version_id', $request->workflow_version_id)
            ->where('sequence', $sequence)->orderBy('id')->get();

        foreach ($stages as $stage) {
            if (! $this->conditionsMatch($stage, $request)) {
                continue; // conditional stage doesn't apply
            }
            AccessApprovalTask::create([
                'request_id'    => $request->id,
                'stage_id'      => $stage->id,
                'sequence'      => $stage->sequence,
                'approver_role' => $stage->approver_role,
                'status'        => 'pending',
                'due_at'        => $stage->sla_hours ? now()->addHours($stage->sla_hours) : null,
            ]);
        }
        $request->update(['current_stage_seq' => $sequence]);
    }

    private function conditionsMatch(AccessWorkflowStage $stage, AccessRequest $request): bool
    {
        if (! $stage->conditions) return true;
        foreach ($stage->conditions as $key => $expected) {
            $actual = match ($key) {
                'request_type' => $request->type,
                'priority'     => $request->priority,
                'branch_id'    => $request->branch_id,
                'department_id' => $request->department_id,
                default => $request->{$key} ?? null,
            };
            if (is_array($expected) ? ! in_array($actual, $expected) : $actual !== $expected) {
                return false;
            }
        }
        return true;
    }

    /**
     * Called after each task decision. Advances the request when the current
     * stage is fully decided; returns the terminal action or null.
     */
    public function advance(AccessRequest $request): ?string
    {
        $seq = $request->current_stage_seq;
        $tasks = AccessApprovalTask::where('request_id', $request->id)->where('sequence', $seq)->get();

        if ($tasks->contains('status', 'rejected')) return 'rejected';
        if ($tasks->contains('status', 'changes_requested')) return 'changes_requested';
        if ($tasks->contains(fn ($t) => in_array($t->status, ['pending', 'delegated'], true))) return null;

        // Stage complete → next sequence with applicable stages, or done.
        $maxSeq = AccessWorkflowStage::where('version_id', $request->workflow_version_id)->max('sequence');
        for ($next = $seq + 1; $next <= $maxSeq; $next++) {
            $applicable = AccessWorkflowStage::where('version_id', $request->workflow_version_id)
                ->where('sequence', $next)->get()
                ->filter(fn ($s) => $this->conditionsMatch($s, $request));
            if ($applicable->isNotEmpty()) {
                $this->emitStageTasks($request, $next);
                return 'advanced';
            }
        }
        return 'completed';
    }

    /** Publish a new workflow version; in-flight requests keep their version. */
    public function publishNewVersion(AccessWorkflow $workflow, array $stages, int $userId): AccessWorkflowVersion
    {
        return DB::transaction(function () use ($workflow, $stages, $userId) {
            $version = $workflow->current_version + 1;
            $v = AccessWorkflowVersion::create([
                'workflow_id' => $workflow->id, 'version' => $version,
                'status' => 'published', 'effective_from' => now()->toDateString(),
            ]);
            foreach (array_values($stages) as $i => $s) {
                AccessWorkflowStage::create([
                    'version_id' => $v->id, 'sequence' => $i + 1,
                    'name' => $s['name'], 'approver_role' => $s['approver_role'],
                    'kind' => $s['kind'] ?? 'sequential', 'sla_hours' => $s['sla_hours'] ?? null,
                    'required' => $s['required'] ?? true, 'conditions' => $s['conditions'] ?? null,
                ]);
            }
            $workflow->update(['current_version' => $version]);
            return $v->load('stages');
        });
    }
}
