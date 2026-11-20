<?php

namespace Modules\Reception\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Reception\Models\AccessApprovalTask;
use Modules\Reception\Services\ApprovalTaskService;

class ApprovalController extends Controller
{
    public function __construct(private ApprovalTaskService $tasks) {}

    public function inbox(Request $r)
    {
        return view('reception::access.approvals.inbox', [
            'tasks' => $this->tasks->inboxFor($r->user()),
        ]);
    }

    public function decide(Request $r, AccessApprovalTask $task)
    {
        $data = $r->validate([
            'decision' => 'required|in:approved,rejected,changes_requested',
            'comment' => 'nullable|string|max:1000',
        ]);
        $this->tasks->decide($task, $r->user(), $data['decision'], $data['comment'] ?? null);
        return back()->with('success', 'Decision recorded.');
    }

    public function delegate(Request $r, AccessApprovalTask $task)
    {
        $data = $r->validate(['delegate_id' => 'required|exists:users,id']);
        $this->tasks->delegate($task, $r->user(), \App\Models\User::findOrFail($data['delegate_id']));
        return back()->with('success', 'Task delegated.');
    }
}
