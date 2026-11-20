<?php
namespace Modules\WorkCenter\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\WorkCenter\Models\{Report, ReportVersion, Document, WorkTask};
use Modules\WorkCenter\Services\{ReportService, DocumentService, TaskService, PdfService};

class WorkCenterController extends Controller
{
    /* ---- dashboard ---- */
    public function dashboard(Request $r)
    {
        $u = $r->user();
        $global = $u->hasRole('super-admin');
        $tasks = WorkTask::query()->when(! $global, fn($q) =>
            $q->where(fn($w) => $w->where('assignee_id', $u->id)->orWhere('branch_id', $u->branch_id)));
        $reports = Report::query()->when(! $global, fn($q) => $q->visibleTo($u));
        return response()->json([
            'my_tasks' => WorkTask::where('assignee_id', $u->id)->whereNotIn('status',['completed','cancelled'])->count(),
            'open_tasks' => (clone $tasks)->whereNotIn('status',['completed','cancelled'])->count(),
            'overdue' => (clone $tasks)->whereNotNull('due_at')->where('due_at','<',now())->whereNotIn('status',['completed','cancelled'])->count(),
            'reports' => (clone $reports)->count(),
            'reports_in_review' => (clone $reports)->where('status','in_review')->count(),
            'documents' => Document::when(! $global, fn($q) => $q->where('company_id', $u->company_id))->count(),
            'expiring_docs' => Document::whereNotNull('expires_at')->where('expires_at','<',now()->addDays(30))->count(),
        ]);
    }

    /* ---- reports ---- */
    public function createReport(Request $r, ReportService $svc)
    {
        $d = $r->validate(['title' => 'required|string|max:255',
            'category_id' => 'required|exists:report_categories,id',
            'template_id' => 'nullable|exists:report_templates,id',
            'type' => 'required|in:builder,upload',
            'period_start' => 'nullable|date', 'period_end' => 'nullable|date',
            'department_id' => 'nullable|exists:departments,id',
            'visibility' => 'required|in:private,reviewers,department,branch,company',
            'classification' => 'required|in:public,internal,confidential,restricted']);
        return response()->json($svc->create($d, $r->user()), 201);
    }

    public function saveContent(Request $r, Report $report, ReportService $svc)
    {
        abort_unless($report->owner_id === $r->user()->id || $r->user()->hasRole('super-admin'), 403);
        $r->validate(['content' => 'required|array', 'note' => 'nullable|string']);
        return response()->json($svc->saveContent($report, $r->input('content'), $r->user(), $r->input('note')), 201);
    }

    public function uploadVersion(Request $r, Report $report, ReportService $svc)
    {
        abort_unless($report->owner_id === $r->user()->id || $r->user()->hasRole('super-admin'), 403);
        $r->validate(['file' => 'required|file|max:10240']);
        return response()->json($svc->saveUpload($report, $r->file('file'), $r->user()), 201);
    }

    public function submitReport(Report $report, Request $r, ReportService $svc)
    {
        abort_unless($report->owner_id === $r->user()->id, 403);
        return response()->json($svc->submit($report, $r->user()));
    }

    public function reviewReport(Request $r, Report $report, ReportService $svc)
    {
        $r->validate(['decision' => 'required|in:approved,rejected,returned', 'comment' => 'nullable|string']);
        return response()->json($svc->review($report, $r->input('decision'), $r->user(), $r->input('comment')));
    }

    public function showReport(Report $report, Request $r, ReportService $svc)
    {
        abort_unless($svc->canView($report, $r->user()), 403);
        return response()->json($report->load('versions','reviews'));
    }

    public function previewPdf(Report $report, int $version, Request $r, ReportService $svc, PdfService $pdf)
    {
        abort_unless($svc->canView($report, $r->user()), 403);
        $v = $report->versions()->where('version', $version)->firstOrFail();
        return $pdf->preview($report, $v);
    }

    /* ---- documents ---- */
    public function uploadDocument(Request $r, DocumentService $svc)
    {
        $d = $r->validate(['title' => 'required|string|max:255',
            'category_id' => 'required|exists:document_categories,id',
            'description' => 'nullable|string', 'tags' => 'nullable|array',
            'visibility' => 'required|in:private,department,branch,company',
            'expires_at' => 'nullable|date', 'file' => 'required|file|max:10240']);
        $file = $d['file']; unset($d['file']);
        return response()->json($svc->upload($d, $file, $r->user()), 201);
    }

    public function documentVersion(Request $r, Document $document, DocumentService $svc)
    {
        abort_unless($svc->canView($document, $r->user()), 403);
        $r->validate(['file' => 'required|file|max:10240', 'note' => 'nullable|string']);
        return response()->json($svc->newVersion($document, $r->file('file'), $r->user(), $r->input('note')), 201);
    }

    public function shareDocument(Request $r, Document $document, DocumentService $svc)
    {
        abort_unless($document->owner_id === $r->user()->id || $r->user()->hasRole('super-admin'), 403);
        $r->validate(['user_id' => 'required|exists:users,id', 'permission' => 'required|in:view,download']);
        return response()->json($svc->share($document, \App\Models\User::findOrFail($r->user_id), $r->user(), $r->input('permission')), 201);
    }

    /* ---- tasks ---- */
    public function assignTask(Request $r, TaskService $svc)
    {
        $d = $r->validate(['title' => 'required|string|max:255', 'description' => 'nullable|string',
            'priority' => 'required|in:critical,high,medium,low',
            'assignee_id' => 'required|exists:users,id', 'reviewer_id' => 'nullable|exists:users,id',
            'department_id' => 'nullable|exists:departments,id', 'branch_id' => 'nullable|exists:branches,id',
            'due_at' => 'nullable|date', 'requires_review' => 'nullable|boolean']);
        return response()->json($svc->assign($d, $r->user()), 201);
    }

    public function taskMove(Request $r, WorkTask $task, TaskService $svc)
    {
        $r->validate(['to' => 'required|string']);
        return response()->json($svc->move($task, $r->input('to'), $r->user()));
    }

    public function taskProgress(Request $r, WorkTask $task, TaskService $svc)
    {
        $r->validate(['progress' => 'required|integer|min:0|max:100', 'note' => 'nullable|string']);
        return response()->json($svc->progress($task, $r->integer('progress'), $r->user(), $r->input('note')), 201);
    }

    public function taskReview(Request $r, WorkTask $task, TaskService $svc)
    {
        $r->validate(['decision' => 'required|in:approved,revision', 'comment' => 'nullable|string']);
        return response()->json($svc->review($task, $r->input('decision'), $r->user(), $r->input('comment')));
    }

    public function myTasks(Request $r)
    {
        return response()->json(WorkTask::where('assignee_id', $r->user()->id)
            ->whereNotIn('status',['cancelled'])->orderByRaw("status='completed'")->latest('due_at')->get());
    }
}
