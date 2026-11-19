<?php

namespace Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;
use Modules\Ticketing\Models\KnowledgeArticle;
use Modules\Ticketing\Models\KnowledgeCategory;
use Modules\Ticketing\Models\TicketAutomationRule;
use Modules\Ticketing\Models\TicketEscalationRule;
use Modules\Ticketing\Models\TicketSlaPolicy;
use Modules\Ticketing\Models\TicketTeam;
use Modules\Ticketing\Models\TicketType;
use Modules\Ticketing\Services\TeamService;

class AdminController extends Controller
{
    public function __construct(private TeamService $teams) {}

    /* ---------- Teams ---------- */
    public function teams(Request $request)
    {
        abort_unless($request->user()->can('ticket.view_all'), 403);

        return view('ticketing::admin.teams', [
            'teams' => TicketTeam::with('members.personal', 'leader.personal', 'department:id,name')->get(),
            'employees' => Employee::where('status', '!=', 'separated')->with('personal')->get(),
            'workload' => fn ($team) => $this->teams->workload($team),
        ]);
    }

    public function storeTeam(Request $request)
    {
        abort_unless($request->user()->can('ticket.view_all'), 403);
        $data = $request->validate([
            'code' => 'required|string|max:30|unique:ticket_teams,code',
            'name' => 'required|string|max:255',
            'leader_employee_id' => 'nullable|exists:employees,id',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:employees,id',
        ]);
        $team = TicketTeam::create($data);
        $team->members()->sync($data['member_ids'] ?? []);

        return back()->with('success', 'Team created.');
    }

    public function syncMembers(Request $request, TicketTeam $team)
    {
        abort_unless($request->user()->can('ticket.view_all'), 403);
        $data = $request->validate(['member_ids' => 'array', 'member_ids.*' => 'exists:employees,id']);
        $team->members()->sync($data['member_ids'] ?? []);

        return back()->with('success', 'Members updated.');
    }

    /* ---------- SLA ---------- */
    public function sla(Request $request)
    {
        abort_unless($request->user()->can('sla.manage'), 403);
        return view('ticketing::admin.sla', [
            'policies' => TicketSlaPolicy::with('type:id,name')->get(),
            'types' => TicketType::active()->get(),
        ]);
    }

    public function storeSla(Request $request)
    {
        abort_unless($request->user()->can('sla.manage'), 403);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'priority' => 'nullable|in:'.implode(',', \Modules\Ticketing\Models\Ticket::PRIORITIES),
            'ticket_type_id' => 'nullable|exists:ticket_types,id',
            'response_minutes' => 'required|integer|min:5',
            'resolution_minutes' => 'required|integer|min:30',
            'business_hours_start' => 'nullable|integer|min:0|max:23',
            'business_hours_end' => 'nullable|integer|min:1|max:24',
            'warn_percent' => 'nullable|integer|min:50|max:99',
        ]);
        TicketSlaPolicy::create($data);

        return back()->with('success', 'SLA policy saved.');
    }

    /* ---------- Escalations ---------- */
    public function escalations(Request $request)
    {
        abort_unless($request->user()->can('escalation.manage'), 403);
        return view('ticketing::admin.escalations', [
            'rules' => TicketEscalationRule::with('toAgent:id,name')->get(),
            'teams' => TicketTeam::active()->get(),
            'users' => \App\Models\User::where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeEscalation(Request $request)
    {
        abort_unless($request->user()->can('escalation.manage'), 403);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'trigger' => 'required|in:sla_breach,unassigned,overdue,critical,warning',
            'priority' => 'nullable|in:'.implode(',', \Modules\Ticketing\Models\Ticket::PRIORITIES),
            'after_minutes' => 'nullable|integer|min:1',
            'to_team_id' => 'nullable|exists:ticket_teams,id',
            'to_agent_id' => 'nullable|exists:users,id',
            'notify_user_id' => 'nullable|exists:users,id',
            'new_priority' => 'nullable|in:'.implode(',', \Modules\Ticketing\Models\Ticket::PRIORITIES),
        ]);
        TicketEscalationRule::create($data);

        return back()->with('success', 'Escalation rule added.');
    }

    /* ---------- Automation ---------- */
    public function automation(Request $request)
    {
        abort_unless($request->user()->can('ticket_automation.manage'), 403);
        return view('ticketing::admin.automation', [
            'rules' => TicketAutomationRule::withCount('logs')->get(),
            'teams' => TicketTeam::active()->get(),
        ]);
    }

    public function storeAutomation(Request $request)
    {
        abort_unless($request->user()->can('ticket_automation.manage'), 403);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'event' => 'required|in:created,updated,scheduled',
            'action' => 'required|in:assign_team,assign_category_default,set_priority,notify_managers,auto_close',
            'priority' => 'nullable|in:'.implode(',', \Modules\Ticketing\Models\Ticket::PRIORITIES),
            'team_id' => 'nullable|exists:ticket_teams,id',
            'after_hours' => 'nullable|integer|min:1',
            'set_priority' => 'nullable|in:'.implode(',', \Modules\Ticketing\Models\Ticket::PRIORITIES),
        ]);
        TicketAutomationRule::create([
            'name' => $data['name'], 'event' => $data['event'], 'action' => $data['action'],
            'conditions' => $data['priority'] ? ['priority' => $data['priority']] : null,
            'action_params' => array_filter(['team_id' => $data['team_id'] ?? null, 'after_hours' => $data['after_hours'] ?? null, 'priority' => $data['set_priority'] ?? null]),
        ]);

        return back()->with('success', 'Automation rule added.');
    }

    public function toggleAutomation(TicketAutomationRule $rule)
    {
        abort_unless(request()->user()->can('ticket_automation.manage'), 403);
        $rule->update(['is_active' => ! $rule->is_active]);

        return back()->with('success', 'Rule '.($rule->is_active ? 'enabled' : 'disabled').'.');
    }

    /* ---------- Reports ---------- */
    public function reports(Request $request)
    {
        abort_unless($request->user()->can('ticket_reports.view'), 403);
        $report = app(\Modules\Ticketing\Services\TicketService::class)
            ->reports($request->user(), $request->from, $request->to);

        return view('ticketing::admin.reports', compact('report'));
    }

    /* ---------- Knowledge Base ---------- */
    public function kbIndex(Request $request)
    {
        $articles = KnowledgeArticle::with('category', 'author:id,name')
            ->when(! $request->user()->can('knowledge_base.manage'), fn ($q) => $q->published())
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%")->orWhere('body', 'like', "%{$s}%"))
            ->latest()->paginate(15)->withQueryString();

        return view('ticketing::kb.index', [
            'articles' => $articles,
            'categories' => KnowledgeCategory::where('is_active', true)->get(),
            'canManage' => $request->user()->can('knowledge_base.manage'),
        ]);
    }

    public function kbShow(KnowledgeArticle $article)
    {
        abort_if($article->status !== 'published' && ! request()->user()->can('knowledge_base.manage'), 404);
        $article->increment('view_count');

        return view('ticketing::kb.show', compact('article'));
    }

    public function kbStore(Request $request)
    {
        abort_unless($request->user()->can('knowledge_base.manage'), 403);
        $data = $request->validate([
            'title' => 'required|string|max:255', 'body' => 'required|string',
            'category_id' => 'nullable|exists:knowledge_categories,id',
            'status' => 'required|in:draft,published',
        ]);
        $article = KnowledgeArticle::create($data + [
            'reference' => 'KB-'.str_pad((string) (KnowledgeArticle::max('id') + 1), 4, '0', STR_PAD_LEFT),
            'author_id' => $request->user()->id,
        ]);

        return redirect()->route('ticketing.kb.show', $article)->with('success', 'Article saved.');
    }
}
