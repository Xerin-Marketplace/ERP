<?php

namespace Modules\Tender\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Tender\Models\Tender;
use Modules\Tender\Models\TenderContract;
use Modules\Tender\Models\TenderEvent;
use Modules\Tender\Models\TenderSubmission;
use Modules\Tender\Services\{BidNoBidService, BoqService, SubmissionGate, TenderWorkflow};

class TenderController extends Controller
{
    public function dashboard(Request $r)
    {
        $q = Tender::query();
        if (! $r->user()->hasRole('super-admin')) {
            $q->where(fn ($q) => $q->where('branch_id', $r->user()->branch_id)->orWhereNull('branch_id'));
        }
        return view('tender::dashboard', [
            'byStage' => (clone $q)->select('stage', DB::raw('count(*) c'))->groupBy('stage')->pluck('c'),
            'upcoming' => (clone $q)->whereNotNull('submission_deadline')->where('submission_deadline','>',now())
                ->orderBy('submission_deadline')->limit(8)->get(),
            'totalValue' => (clone $q)->sum('submitted_value'),
            'awardedValue' => TenderContract::sum('value'),
        ]);
    }

    public function index(Request $r)
    {
        $tenders = Tender::with('assessment')->latest()
            ->when($r->filled('stage'), fn ($q) => $q->where('stage', $r->input('stage')))
            ->when($r->filled('buyer_type'), fn ($q) => $q->where('buyer_type', $r->input('buyer_type')))
            ->paginate(20);
        return view('tender::index', compact('tenders'));
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'reference' => 'required|string|max:60', 'buyer_name' => 'required|string|max:150',
            'buyer_type' => 'required|in:public,private,international,ngo',
            'category' => 'required|in:goods,works,services,consultancy,framework,jv,subcontract',
            'title' => 'required|string|max:255', 'description' => 'nullable|string',
            'location' => 'nullable|string|max:150',
            'source' => 'nullable|in:nest,portal,newspaper,email,referral,manual',
            'source_url' => 'nullable|url|max:255',
            'estimated_value' => 'nullable|numeric|min:0',
            'security_required' => 'nullable|numeric|min:0',
            'performance_security_pct' => 'nullable|numeric|min:0|max:100',
            'document_fee' => 'nullable|numeric|min:0',
            'currency' => 'required|size:3',
            'published_at' => 'nullable|date', 'prebid_meeting_at' => 'nullable|date',
            'clarification_deadline' => 'nullable|date',
            'submission_deadline' => 'nullable|date', 'validity_days' => 'nullable|integer|min:0',
        ]);
        $d['company_id'] = $r->user()->company_id ?? \Modules\Organization\Models\Company::value('id');
        $d['branch_id'] = $r->user()->branch_id;
        $d['created_by'] = $r->user()->id;
        try {
            $t = Tender::create($d);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return back()->withErrors(['reference' => 'Duplicate tender: this reference + buyer already exists.']);
        }
        return redirect()->route('tender.show', $t)->with('success', 'Opportunity registered.');
    }

    public function show(Tender $tender, Request $r)
    {
        $tender->load(['assessment','activeBoq.items','team','approvals','securities','events','submission','contracts']);
        return view('tender::show', [
            't' => $tender,
            'boqTotals' => $tender->activeBoq ? app(BoqService::class)->totals($tender->activeBoq) : null,
            'checklist' => app(SubmissionGate::class)->checklist($tender),
            'nextStages' => collect(Tender::STAGES)->filter(fn ($s) => app(TenderWorkflow::class)->can($tender, $s)),
        ]);
    }

    public function assess(Tender $tender, Request $r, BidNoBidService $svc)
    {
        $svc->assess($tender, $r->input('scores', []), $r->user());
        return back()->with('success', 'Assessment saved.');
    }

    public function decide(Tender $tender, Request $r, BidNoBidService $svc)
    {
        $r->validate(['decision' => 'required|in:bid,no_bid,conditional_bid', 'reason' => 'nullable|string|max:500']);
        $svc->decide($tender, $r->input('decision'), $r->user(), $r->input('reason'));
        return back()->with('success', 'Decision recorded.');
    }

    public function addBoqItem(Tender $tender, Request $r, BoqService $svc)
    {
        $boq = $tender->activeBoq ?? $tender->boqs()->create(['version' => 1]);
        $d = $r->validate(['item_no' => 'required|string|max:30', 'description' => 'required|string',
            'section' => 'nullable|string|max:100', 'unit' => 'nullable|string|max:20',
            'quantity' => 'required|numeric|min:0',
            'material_cost' => 'nullable|numeric|min:0', 'labour_cost' => 'nullable|numeric|min:0',
            'equipment_cost' => 'nullable|numeric|min:0', 'transport_cost' => 'nullable|numeric|min:0',
            'other_cost' => 'nullable|numeric|min:0', 'subcontract_cost' => 'nullable|numeric|min:0',
            'unit_rate' => 'nullable|numeric|min:0', 'remarks' => 'nullable|string|max:500']);
        $svc->addItem($boq, $d);
        return back()->with('success', 'BOQ item added.');
    }

    public function submit(Tender $tender, Request $r)
    {
        $r->validate(['method' => 'required|in:nest,portal,physical,email',
            'platform_ref' => 'nullable|string|max:100', 'evidence_document_id' => 'required|exists:tender_documents,id']);
        $gate = app(SubmissionGate::class);
        abort_unless($gate->ready($tender), 422, 'Submission checklist incomplete.');
        DB::transaction(function () use ($tender, $r) {
            TenderSubmission::create(['tender_id' => $tender->id, 'method' => $r->input('method'),
                'platform_ref' => $r->input('platform_ref'), 'submitted_at' => now(),
                'submitted_by' => $r->user()->id,
                'evidence_document_id' => $r->input('evidence_document_id')]);
            app(TenderWorkflow::class)->transition($tender, 'submitted', $r->user(), null, true);
        });
        return back()->with('success', 'Tender submitted — awaiting evidence verification.');
    }

    public function event(Tender $tender, Request $r)
    {
        $r->validate(['type' => 'required|string|max:40', 'note' => 'nullable|string|max:1000',
            'event_date' => 'required|date']);
        TenderEvent::create(['tender_id' => $tender->id, 'type' => $r->input('type'),
            'note' => $r->input('note'), 'event_date' => $r->input('event_date'),
            'recorded_by' => $r->user()->id]);
        $map = ['under_eval' => 'evaluation', 'clarification' => 'clarification', 'negotiation' => 'negotiation'];
        if (isset($map[$r->input('type')])) {
            app(TenderWorkflow::class)->transition($tender, $map[$r->input('type')], $r->user());
        }
        return back()->with('success', 'Event recorded.');
    }

    public function award(Tender $tender, Request $r)
    {
        $r->validate(['contract_ref' => 'required|string|max:60', 'value' => 'required|numeric|min:0']);
        abort_unless($tender->stage === 'evaluation' || $tender->submission()->exists(), 422,
            'Award requires a submitted tender under evaluation.');
        DB::transaction(function () use ($tender, $r) {
            $tender->contracts()->create(['contract_ref' => $r->input('contract_ref'),
                'value' => $r->input('value'), 'currency' => $tender->currency]);
            $tender->update(['submitted_value' => $r->input('value')]);
            app(TenderWorkflow::class)->transition($tender, 'awarded', $r->user());
        });
        return back()->with('success', 'Award recorded — contract created.');
    }

    /* ---- Phase 2: clarifications / tasks / award / variations / intelligence ---- */

    public function intelligence(Tender $tender)
    {
        return response()->json(app(\Modules\Tender\Services\TenderIntelligence::class)->analyze($tender));
    }

    public function clarify(Request $r, Tender $tender)
    {
        $d = $r->validate(['direction' => 'required|in:received,sent',
            'subject' => 'required|string|max:255', 'content' => 'required|string',
            'response' => 'nullable|string', 'received_at' => 'nullable|date']);
        $c = $tender->clarifications()->create($d + [
            'status' => isset($d['response']) ? 'responded' : 'open',
            'responded_at' => isset($d['response']) ? now() : null,
            'handled_by' => $r->user()->id,
        ]);
        if ($tender->stage === 'evaluation') {
            app(\Modules\Tender\Services\TenderWorkflow::class)->transition($tender, 'clarification', $r->user());
        }
        return response()->json($c, 201);
    }

    public function respondClarification(Request $r, \Modules\Tender\Models\TenderClarification $clarification)
    {
        $r->validate(['response' => 'required|string']);
        $clarification->update(['response' => $r->input('response'),
            'responded_at' => now(), 'status' => 'responded', 'handled_by' => $r->user()->id]);
        return response()->json($clarification);
    }

    public function addTask(Request $r, Tender $tender)
    {
        $d = $r->validate(['title' => 'required|string|max:255',
            'raci' => 'required|in:R,A,C,I', 'assignee_id' => 'required|exists:users,id',
            'due_at' => 'nullable|date', 'note' => 'nullable|string']);
        return response()->json($tender->tasks()->create($d), 201);
    }

    public function updateTask(Request $r, \Modules\Tender\Models\TenderTask $task)
    {
        $d = $r->validate(['status' => 'required|in:open,in_progress,done,blocked',
            'note' => 'nullable|string']);
        $task->update($d);
        return response()->json($task);
    }

    public function recordAward(Request $r, Tender $tender)
    {
        $d = $r->validate(['award_ref' => 'nullable|string|max:100',
            'awarded_at' => 'required|date', 'value' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3', 'scope' => 'nullable|string',
            'conditions' => 'nullable|string', 'contract_sign_deadline' => 'nullable|date',
            'notice_document_id' => 'nullable|exists:tender_documents,id']);
        $award = $tender->awards()->create($d + ['recorded_by' => $r->user()->id]);
        $tender->update(['submitted_value' => $d['value']]);
        if ($tender->stage !== 'awarded') {
            app(\Modules\Tender\Services\TenderWorkflow::class)->transition($tender, 'awarded', $r->user());
        }
        return response()->json($award, 201);
    }

    public function variation(Request $r, \Modules\Tender\Models\TenderContract $contract)
    {
        $d = $r->validate(['ref' => 'nullable|string|max:100',
            'type' => 'required|in:scope,value,time,payment_terms,other',
            'description' => 'required|string',
            'value_delta' => 'nullable|numeric', 'days_delta' => 'nullable|integer']);
        return response()->json($contract->variations()->create($d), 201);
    }

    public function approveVariation(Request $r, \Modules\Tender\Models\TenderContractVariation $variation)
    {
        abort_if($variation->status !== 'draft', 422, 'Already decided.');
        $variation->update(['status' => 'approved', 'approved_by' => $r->user()->id]);
        $contract = $variation->contract;
        $contract->value = bcadd((string) $contract->value, (string) $variation->value_delta, 2);
        if ($variation->days_delta > 0 && $contract->ends_at) {
            $contract->ends_at = $contract->ends_at->addDays($variation->days_delta);
        }
        $contract->save();
        return response()->json($variation->fresh());
    }
}